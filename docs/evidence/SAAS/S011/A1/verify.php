<?php
// Independent S011 read-only verifier; credentials and row contents never emitted.
try {
    require getcwd().'/vendor/autoload.php';
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $schema = $db->getSchemaBuilder();
    $private = '/home/muzib/deploy-demo-repair-app/saas-s010-20261117T041815Z';
    if ($db->getDatabaseName() !== trim(file_get_contents($private.'/ops/live-db-name'))) throw new RuntimeException('target');
    $db->beforeExecuting(function ($sql) {
        if (!preg_match('/^\\s*(SELECT|SHOW)\\b/i', $sql)) throw new RuntimeException('non-read-query-blocked');
    });
    $expect = fn ($condition, $name) => $condition ?: throw new RuntimeException($name);
    $base = json_decode(file_get_contents($private.'/backup/inventory.json'), true, 512, JSON_THROW_ON_ERROR);
    unset($base['sessions']);
    $afterAuth = getenv('S011_AFTER_AUTH') === '1';
    $originalTokens = []; $rotatedTokens = 0;
    if ($afterAuth) {
        unset($base['cache']); // Framework login throttling mutates cache; business fields remain compared.
        $sqlFiles = glob($private.'/backup/*.sql.gz');
        $expect(count($sqlFiles) === 1, 'dump-count');
        $sql = gzdecode(file_get_contents($sqlFiles[0]));
        $expect(is_string($sql), 'dump-compression');
        $expect((bool) preg_match('/^INSERT INTO `users` VALUES\s*(.*?);$/ms', $sql, $insert), 'dump-users');
        $columns = $base['users']['columns'];
        $idIndex = array_search('id', $columns, true);
        $tokenIndex = array_search('remember_token', $columns, true);
        $expect($idIndex !== false && $tokenIndex !== false, 'dump-columns');
        // Tokenize only mysqldump literal rows; quoted contents never leave memory or execute as SQL.
        $pattern = "~'(?:[^'\\\\]|\\\\.)*'|NULL|-?[0-9]+|[(),]~s";
        $expect(trim(preg_replace($pattern, '', $insert[1])) === '', 'dump-literals-only');
        preg_match_all($pattern, $insert[1], $tokens);
        $row = null;
        foreach ($tokens[0] as $token) {
            if ($token === '(') { $expect($row === null, 'dump-row-start'); $row = []; }
            elseif ($token === ')') {
                $expect(is_array($row) && count($row) === count($columns), 'dump-row-shape');
                $id = (int) $row[$idIndex]; $raw = $row[$tokenIndex];
                $expect(!array_key_exists($id, $originalTokens), 'dump-unique-user');
                $expect($raw === 'NULL' || preg_match("/^'[A-Za-z0-9]+'$/", $raw), 'dump-token-shape');
                $originalTokens[$id] = $raw === 'NULL' ? null : substr($raw, 1, -1); $row = null;
            } elseif ($token !== ',') { $expect(is_array($row), 'dump-row-value'); $row[] = $token; }
        }
        $expect($row === null && count($originalTokens) === $base['users']['rows'], 'dump-users-count');
        unset($sql, $insert, $tokens);
    }
    $actual = [];
    foreach ($base as $table => $inventory) {
        $rows = $db->table($table)->select($inventory['columns'])->get()->map(function ($row) use ($afterAuth, $table, $originalTokens, &$rotatedTokens, $expect) {
            $row = (array) $row;
            if ($afterAuth && $table === 'users') {
                $expect(array_key_exists((int)$row['id'], $originalTokens), 'user-token-id');
                $rotatedTokens += $row['remember_token'] !== $originalTokens[(int)$row['id']] ? 1 : 0;
                $row['remember_token'] = $originalTokens[(int)$row['id']];
            }
            ksort($row); return json_encode($row, JSON_THROW_ON_ERROR);
        })->all();
        sort($rows);
        $actual[$table] = ['columns'=>$inventory['columns'], 'rows'=>count($rows), 'sha256'=>hash('sha256', implode("\n", $rows))];
    }
    ksort($actual); ksort($base);
    $mismatched = array_keys(array_filter($actual, fn($i,$t)=>$i!==$base[$t], ARRAY_FILTER_USE_BOTH));
    $expect(!$mismatched, 'legacy-fields:'.implode(',', $mismatched));
    $tenant = $db->table('tenants')->get(); $store = $db->table('store_settings')->first();
    $expect($tenant->count() === 1 && (int)$tenant[0]->id === 1 && $tenant[0]->is_active && $tenant[0]->name === $store->name, 'tenant-identity');
    $roots = [];
    foreach (['users','categories','service_orders','products','sales','expenses','number_sequences','store_settings'] as $table) {
        $column = collect($schema->getColumns($table))->firstWhere('name','tenant_id');
        $fk = collect($schema->getForeignKeys($table))->contains(fn($f)=>$f['columns']===['tenant_id'] && $f['foreign_table']==='tenants');
        $invalid = $db->table($table)->whereNull('tenant_id')->orWhere('tenant_id','!=',1)->count();
        $expect(!$column['nullable'] && $fk && $invalid === 0, 'tenant-root');
        $roots[$table] = ['rows'=>$db->table($table)->count(), 'required'=>true, 'fk'=>true, 'violations'=>$invalid];
    }
    app(App\Support\TenantContext::class)->set(App\Models\Tenant::findOrFail(1));
    foreach ([App\Models\User::class,App\Models\Category::class,App\Models\Product::class,App\Models\ServiceOrder::class,App\Models\Sale::class,App\Models\Expense::class,App\Models\StoreSetting::class] as $class) {
        $expect($class::count() === $db->table((new $class)->getTable())->count(), 'scoped-count');
    }
    $q = fn($v)=>'`'.str_replace('`','``',$v).'`'; $fks=0;
    foreach ($schema->getTables($db->getDatabaseName()) as $table) foreach ($schema->getForeignKeys($table['name']) as $fk) {
        $expect($fk['foreign_schema'] === $db->getDatabaseName(), 'cross-schema');
        $on=[]; $nn=[];
        foreach ($fk['columns'] as $i=>$column) { $on[]='c.'.$q($column).'=p.'.$q($fk['foreign_columns'][$i]); $nn[]='c.'.$q($column).' IS NOT NULL'; }
        $sql='SELECT COUNT(*) n FROM '.$q($table['name']).' c LEFT JOIN '.$q($fk['foreign_table']).' p ON '.implode(' AND ',$on).' WHERE '.implode(' AND ',$nn).' AND p.'.$q($fk['foreign_columns'][0]).' IS NULL';
        $expect((int)$db->selectOne($sql)->n===0,'orphan'); $fks++;
    }
    auth()->setUser(App\Models\User::query()->where('username','admin')->where('is_active',true)->firstOrFail());
    $reportBase=json_decode(file_get_contents($private.'/ops/app-baseline.json'),true,512,JSON_THROW_ON_ERROR);
    $hashes=[];
    foreach ($reportBase['ranges'] as [$from,$to]) $hashes[]=hash('sha256',json_encode(app(App\Services\Reports\Report::class)->build($from,$to),JSON_THROW_ON_ERROR));
    $expect($hashes === $reportBase['reports'],'baseline-report');
    $period=['from'=>now('Asia/Makassar')->startOfMonth()->toDateString(),'to'=>now('Asia/Makassar')->toDateString()];
    $sqlTotals=function($from,$to) use ($db) {
        $service=$db->selectOne("SELECT COALESCE(SUM(i.qty*i.price),0) amount, COALESCE(SUM(i.qty*i.cost),0) cost FROM service_orders o JOIN service_order_items i ON i.service_order_id=o.id WHERE o.tenant_id=1 AND o.status IN ('selesai','diambil') AND DATE(o.invoiced_on) BETWEEN ? AND ?",[$from,$to]);
        $sales=$db->selectOne('SELECT COALESCE(SUM(i.qty*i.price),0) amount, COALESCE(SUM(i.qty*i.cost),0) cost FROM sales s JOIN sale_items i ON i.sale_id=s.id WHERE s.tenant_id=1 AND DATE(s.sold_on) BETWEEN ? AND ?',[$from,$to]);
        $expenses=$db->table('expenses')->where('tenant_id',1)->whereBetween('spent_on',[$from,$to])->sum('amount');
        $restock=$db->selectOne('SELECT COALESCE(SUM(e.qty*e.cost_price),0) amount FROM stock_entries e JOIN products p ON e.product_id=p.id WHERE p.tenant_id=1 AND DATE(e.entry_date) BETWEEN ? AND ?',[$from,$to])->amount;
        $revenue=(int)$service->amount+(int)$sales->amount; $cost=(int)$service->cost+(int)$sales->cost;
        return ['service'=>(string)(int)$service->amount,'sales'=>(string)(int)$sales->amount,'revenue'=>(string)$revenue,'cost'=>(string)$cost,'gross'=>(string)($revenue-$cost),'expenses'=>(string)(int)$expenses,'net'=>(string)($revenue-$cost-$expenses),'restock'=>(string)(int)$restock];
    };
    $reports=[];
    foreach (['month'=>[$period['from'],$period['to']],'today'=>[$period['to'],$period['to']]] as $label=>[$from,$to]) {
        $sql=$sqlTotals($from,$to); $appTotals=app(App\Services\Reports\Report::class)->build($from,$to);
        foreach($sql as $key=>$value) $expect($appTotals[$key]===$value,'sql-report-'.$key);
        $reports[$label]=['from'=>$from,'to'=>$to,'totals'=>$sql,'sql_app_equal'=>true];
    }
    $migration=$db->table('migrations')->where('migration','like','%tenant%')->first();
    $expect($migration && (int)$migration->batch===2,'migration');
    $expect(hash('sha256',config('app.key'))===trim(file_get_contents($private.'/ops/appkey.sha256')),'key');
    echo json_encode(['verdict'=>'pass','utc'=>gmdate('c'),'version'=>$db->selectOne('SELECT VERSION() v')->v,'app_timezone'=>config('app.timezone'),'db_timezone'=>$db->selectOne('SELECT @@session.time_zone session_tz, @@system_time_zone system_tz'),'tenant_count'=>1,'tenant_active'=>true,'tenant_identity_matches_store'=>true,'roots'=>$roots,'scoped_models_equal'=>7,'foreign_keys_checked'=>$fks,'orphan_violations'=>0,'cross_tenant_violations'=>0,'legacy_tables_compared'=>count($actual),'users_non_authentication_fields_identical'=>true,'remember_tokens_rotated'=>$rotatedTokens,'framework_cache_excluded'=>$afterAuth,'sessions_excluded_symmetrically'=>true,'legacy_inventory_projection_sha256'=>hash('sha256',json_encode($actual,JSON_THROW_ON_ERROR)),'legacy_counts'=>array_map(fn($i)=>$i['rows'],$actual),'report_baseline_sha256'=>hash_file('sha256',$private.'/ops/app-baseline.json'),'baseline_reports_identical'=>true,'reports'=>$reports,'migration'=>$migration->migration,'migration_batch'=>$migration->batch,'key_preserved'=>true,'logo_path_present'=>!empty($store->logo_path)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $e) { fwrite(STDERR,'S011 verifier failed: '.get_class($e).($e instanceof RuntimeException && preg_match('/^[a-z0-9_:-]+(?:,[a-z0-9_]+)*$/', $e->getMessage()) ? ' ['.$e->getMessage().']' : '')."\n"); exit(1); }

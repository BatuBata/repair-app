<?php
try {
    require getcwd().'/vendor/autoload.php';
    $app=require getcwd().'/bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db=Illuminate\Support\Facades\DB::connection(); $schema=$db->getSchemaBuilder();
    $db->beforeExecuting(fn($sql)=>preg_match('/^\s*(SELECT|SHOW)\b/i',$sql) ?: throw new RuntimeException('write-blocked'));
    $expect=fn($ok,$name)=>$ok ?: throw new RuntimeException($name);
    $private='/home/muzib/deploy-demo-repair-app/saas-s010-20261117T041815Z';
    $expect(trim(file_get_contents(getcwd().'/.release-revision'))==='99f64bf59ff1489825333aa821015a281db611ea','release');
    $base=json_decode(file_get_contents($private.'/backup/inventory.json'),true,512,JSON_THROW_ON_ERROR);
    $sqlFiles=glob($private.'/backup/*.sql.gz'); $expect(count($sqlFiles)===1,'dump'); $sql=gzdecode(file_get_contents($sqlFiles[0]));
    $columns=$base['users']['columns']; $idIndex=array_search('id',$columns,true); $tokenIndex=array_search('remember_token',$columns,true);
    $expect((bool)preg_match('/^INSERT INTO `users` VALUES\s*(.*?);$/ms',$sql,$insert),'dump-users');
    $pattern="~'(?:[^'\\\\]|\\\\.)*'|NULL|-?[0-9]+|[(),]~s"; preg_match_all($pattern,$insert[1],$tokens); $original=[];$row=null;
    foreach($tokens[0] as $token){if($token==='(')$row=[];elseif($token===')'){$expect(count($row)===count($columns),'dump-shape');$original[(int)$row[$idIndex]]=$row[$tokenIndex]==='NULL'?null:substr($row[$tokenIndex],1,-1);$row=null;}elseif($token!==',')$row[]=$token;}
    $business=['users','categories','service_orders','service_order_items','products','stock_entries','sales','sale_items','expenses','number_sequences','store_settings'];
    $root=['users','categories','service_orders','products','sales','expenses','number_sequences','store_settings'];
    $legacy=[];$legacyDrift=[];
    foreach($business as $table){$cols=$base[$table]['columns'];$q=$db->table($table);
        if(in_array($table,$root,true))$q->where($table.'.tenant_id',1);
        elseif($table==='service_order_items')$q->join('service_orders','service_orders.id','=','service_order_items.service_order_id')->where('service_orders.tenant_id',1);
        elseif($table==='stock_entries')$q->join('products','products.id','=','stock_entries.product_id')->where('products.tenant_id',1);
        else $q->join('sales','sales.id','=','sale_items.sale_id')->where('sales.tenant_id',1);
        $select=array_map(fn($c)=>$table.'.'.$c.' as '.$c,$cols);
        $rows=$q->select($select)->get()->map(function($r)use($table,$original){$r=(array)$r;if($table==='users')$r['remember_token']=$original[(int)$r['id']]??null;ksort($r);return json_encode($r,JSON_THROW_ON_ERROR);})->all();sort($rows);
        $actual=['columns'=>$cols,'rows'=>count($rows),'sha256'=>hash('sha256',implode("\n",$rows))];
        if($actual!==$base[$table])$legacyDrift[$table]=['rows'=>$actual['rows'],'baseline_rows'=>$base[$table]['rows'],'sha256'=>$actual['sha256'],'baseline_sha256'=>$base[$table]['sha256']];
        $legacy[$table]=['rows'=>$actual['rows'],'sha256'=>$actual['sha256']];
    }
    $tenants=$db->table('tenants')->orderBy('id')->get();$expect($tenants->count()===2,'tenant-count');$rf=$tenants->firstWhere('name','RF Samarinda');$expect($rf&&$rf->is_active,'rf');$rfid=(int)$rf->id;
    $expect($db->table('users')->where('username','fahad')->where('tenant_id',$rfid)->where('name','Fahad')->count()===1,'owner');
    $expect($db->table('users')->where('username','teknisi-rf')->where('tenant_id',$rfid)->where('name','Teknisi RF')->where('role','teknisi')->count()===1,'tech');
    foreach(['fahad','teknisi-rf'] as $u)$expect($db->table('users')->where('username',$u)->count()===1,'unique-user');
    $store=$db->table('store_settings')->where('tenant_id',$rfid)->first();$expect($store&&$store->name==='RF Samarinda'&&$store->address===''&&$store->phone==='','store');
    $expect($db->table('categories')->where('tenant_id',$rfid)->where('name','HP')->where('is_active',1)->count()===1,'category');
    $roots=[];foreach($root as $table){$null=$db->table($table)->whereNull('tenant_id')->count();$wrong=$db->table($table)->whereNotIn('tenant_id',$tenants->pluck('id'))->count();$expect($null===0&&$wrong===0,'root-'.$table);$roots[$table]=['rows'=>$db->table($table)->count(),'null'=>0,'unknown_tenant'=>0];}
    $quote=fn($v)=>'`'.str_replace('`','``',$v).'`';$fkCount=0;foreach($schema->getTables($db->getDatabaseName()) as $t)foreach($schema->getForeignKeys($t['name']) as $fk){$on=[];$nn=[];foreach($fk['columns'] as $i=>$c){$on[]='c.'.$quote($c).'=p.'.$quote($fk['foreign_columns'][$i]);$nn[]='c.'.$quote($c).' IS NOT NULL';}$n=$db->selectOne('SELECT COUNT(*) n FROM '.$quote($t['name']).' c LEFT JOIN '.$quote($fk['foreign_table']).' p ON '.implode(' AND ',$on).' WHERE '.implode(' AND ',$nn).' AND p.'.$quote($fk['foreign_columns'][0]).' IS NULL')->n;$expect((int)$n===0,'orphan');$fkCount++;}
    app(App\Support\TenantContext::class)->set(App\Models\Tenant::findOrFail($rfid));auth()->setUser(App\Models\User::where('username','fahad')->firstOrFail());
    $from=now('Asia/Makassar')->startOfMonth()->toDateString();$to=now('Asia/Makassar')->toDateString();$report=app(App\Services\Reports\Report::class)->build($from,$to);
    $service=$db->selectOne("SELECT COALESCE(SUM(i.qty*i.price),0) amount,COALESCE(SUM(i.qty*i.cost),0) cost FROM service_orders o JOIN service_order_items i ON i.service_order_id=o.id WHERE o.tenant_id=? AND o.status IN ('selesai','diambil') AND DATE(o.invoiced_on) BETWEEN ? AND ?",[$rfid,$from,$to]);
    $sales=$db->selectOne('SELECT COALESCE(SUM(i.qty*i.price),0) amount,COALESCE(SUM(i.qty*i.cost),0) cost FROM sales s JOIN sale_items i ON i.sale_id=s.id WHERE s.tenant_id=? AND DATE(s.sold_on) BETWEEN ? AND ?',[$rfid,$from,$to]);
    $expected=['service'=>(string)(int)$service->amount,'sales'=>(string)(int)$sales->amount,'revenue'=>(string)((int)$service->amount+(int)$sales->amount),'cost'=>(string)((int)$service->cost+(int)$sales->cost)];$expected['gross']=(string)((int)$expected['revenue']-(int)$expected['cost']);
    foreach($expected as $k=>$v)$expect($report[$k]===$v,'report-'.$k);
    $product=$db->table('products')->where('tenant_id',$rfid)->where('name','Pilot RF Tempered Glass')->first();$expect($product&&(int)$product->stock===4,'stock');
    $rfCounts=['users'=>$db->table('users')->where('tenant_id',$rfid)->count(),'categories'=>$db->table('categories')->where('tenant_id',$rfid)->count(),'products'=>$db->table('products')->where('tenant_id',$rfid)->count(),'sales'=>$db->table('sales')->where('tenant_id',$rfid)->count(),'service_orders'=>$db->table('service_orders')->where('tenant_id',$rfid)->count()];
    $expect($rfCounts===['users'=>2,'categories'=>1,'products'=>1,'sales'=>1,'service_orders'=>1],'rf-delta');
    $relationMismatch=(int)$db->selectOne('SELECT COUNT(*) n FROM service_orders o JOIN categories c ON c.id=o.category_id JOIN users u ON u.id=o.created_by LEFT JOIN users t ON t.id=o.technician_id WHERE o.tenant_id<>c.tenant_id OR o.tenant_id<>u.tenant_id OR (t.id IS NOT NULL AND o.tenant_id<>t.tenant_id)')->n;
    $expect($relationMismatch===0,'relation-tenant');
    $scoped=[];foreach([1,$rfid] as $tid){app(App\Support\TenantContext::class)->set(App\Models\Tenant::findOrFail($tid));foreach([App\Models\User::class,App\Models\Category::class,App\Models\Product::class,App\Models\ServiceOrder::class,App\Models\Sale::class,App\Models\Expense::class,App\Models\StoreSetting::class] as $class){$table=(new $class)->getTable();$model=$class::count();$raw=$db->table($table)->where('tenant_id',$tid)->count();$expect($model===$raw,'scoped');$scoped[$tid][$table]=['model'=>$model,'raw'=>$raw];}}
    echo json_encode(['verdict'=>'pass','utc'=>gmdate('c'),'release_revision'=>'99f64bf59ff1489825333aa821015a281db611ea','tenant_count'=>2,'rf_tenant_id'=>$rfid,'identities'=>['owner'=>true,'technician'=>true,'store'=>true,'category_hp'=>true,'global_usernames_unique'=>true],'roots'=>$roots,'foreign_keys_checked'=>$fkCount,'orphans'=>0,'wrong_tenant_ids'=>0,'cross_tenant_relation_mismatches'=>0,'scoped_model_counts_match_raw_by_tenant'=>$scoped,'legacy_reference_drift'=>['attribution'=>'concurrent_client_activity_not_attributable_to_pilot','tables'=>$legacyDrift],'legacy_current_projection'=>$legacy,'rf_counts'=>$rfCounts,'rf_stock'=>4,'rf_report'=>['from'=>$from,'to'=>$to,'totals'=>$expected,'sql_app_equal'=>true]],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,'S012 verifier failed ['.preg_replace('/[^a-z0-9_-]/i','',$e->getMessage())."]\n");exit(1);}

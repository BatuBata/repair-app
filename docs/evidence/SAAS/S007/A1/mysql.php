<?php
// Independent S007 checks; credentials and browser tokens remain in a private runtime.
use App\Models\{Tenant,User,Category,Product,StoreSetting,ServiceOrder,Sale,Expense};
use App\Support\TenantContext;
use App\Services\{CreateServiceOrder,DocumentNumber};
use App\Actions\Sales\CreateSale;
use Illuminate\Support\Facades\{DB,Artisan,Schema,Hash};
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
$root=getenv('QA_RUNTIME');
if (!$root || !str_starts_with(realpath($root),'/private/tmp/saas-s007-')) throw new RuntimeException('Private S007 runtime required');
$database='saas_s007_20261117_01';
$local=Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env'));
if (!in_array($local['DB_HOST']??'', ['127.0.0.1','localhost','::1'],true)) throw new RuntimeException('Local only');
foreach(['host','port','username','password','unix_socket'] as $key) config(['database.connections.mysql.'.$key=>$local['DB_'.strtoupper($key)]??($key==='port'?'3306':($key==='unix_socket'?'':null))]);
config(['database.default'=>'mysql','database.connections.mysql.url'=>null,'database.connections.mysql.database'=>$database]); DB::purge('mysql');
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
function out($data){echo json_encode($data,JSON_UNESCAPED_SLASHES)."\n";}
$mode=$argv[1]??'';
if($mode==='setup'){
 $pdo=new PDO('mysql:host='.$local['DB_HOST'].';port='.($local['DB_PORT']??3306),$local['DB_USERNAME'],$local['DB_PASSWORD']??'');
 check(!$pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$database'")->fetch(),'Database must not preexist');
 $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $env=['APP_ENV'=>'local','APP_DEBUG'=>'false','APP_KEY'=>'base64:'.base64_encode(random_bytes(32)),'APP_URL'=>'http://127.0.0.1:8877','DB_CONNECTION'=>'mysql','DB_URL'=>'','DB_DATABASE'=>$database,'DB_HOST'=>$local['DB_HOST'],'DB_PORT'=>$local['DB_PORT']??'3306','DB_USERNAME'=>$local['DB_USERNAME'],'DB_PASSWORD'=>$local['DB_PASSWORD']??'','SESSION_DRIVER'=>'database','SESSION_SECURE_COOKIE'=>'false','CACHE_STORE'=>'array','LOG_CHANNEL'=>'stderr','MAIL_MAILER'=>'array','QUEUE_CONNECTION'=>'sync'];
 file_put_contents($root.'/env.json',json_encode($env));chmod($root.'/env.json',0600);
 // Migrate the historical schema first, then append representative legacy rows.
 $files=glob(database_path('migrations/*.php')); $tenant=array_pop($files);
 foreach($files as $file){check(Artisan::call('migrate',['--path'=>$file,'--realpath'=>true,'--force'=>true])===0,'Legacy migration failed');}
 DB::table('users')->insert(['id'=>1,'name'=>'Legacy owner','username'=>'s007.legacy','password'=>Hash::make(bin2hex(random_bytes(16))),'role'=>'admin','is_active'=>1]);
 DB::table('categories')->insert(['id'=>1,'name'=>'Legacy category','is_active'=>1]);
 DB::table('products')->insert(['id'=>1,'name'=>'Legacy product','cost_price'=>200,'sell_price'=>500,'stock'=>7,'is_active'=>1]);
 DB::table('store_settings')->insert(['id'=>1,'name'=>'Legacy identity','address'=>'Legacy address','phone'=>'6281234567890','default_warranty_days'=>30]);
 DB::table('service_orders')->insert(['id'=>1,'number'=>'TTS-2610-008','customer_name'=>'Legacy customer','customer_phone'=>'6281234567890','category_id'=>1,'complaint'=>'Legacy complaint','created_by'=>1,'tracking_token'=>bin2hex(random_bytes(32))]);
 DB::table('service_order_items')->insert(['service_order_id'=>1,'type'=>'part','description'=>'Legacy part','qty'=>2,'price'=>500,'cost'=>200]);
 DB::table('sales')->insert(['id'=>1,'number'=>'JL-2610-004','sold_on'=>'2026-10-31','created_by'=>1]);
 DB::table('sale_items')->insert(['sale_id'=>1,'product_id'=>1,'product_name'=>'Legacy product','qty'=>1,'price'=>500,'cost'=>200]);
 DB::table('stock_entries')->insert(['product_id'=>1,'qty'=>2,'cost_price'=>200,'entry_date'=>'2026-10-31','created_by'=>1]);
 DB::table('expenses')->insert(['spent_on'=>'2026-10-31','category'=>'bensin','amount'=>50,'created_by'=>1]);
 foreach(['TTS'=>8,'JL'=>4] as $p=>$n)DB::table('number_sequences')->insert(['prefix'=>$p,'period'=>'2610','last_seq'=>$n]);
 $tables=['users','categories','products','store_settings','service_orders','service_order_items','sales','sale_items','stock_entries','expenses','number_sequences'];
 $snapshot=function()use($tables){$r=[];foreach($tables as $t)$r[$t]=DB::table($t)->get()->map(function($row){$row=(array)$row;unset($row['tenant_id']);return $row;})->all();return $r;};
 $before=$snapshot();
 check(Artisan::call('migrate',['--path'=>$tenant,'--realpath'=>true,'--force'=>true])===0,'Tenant migration failed');
 check($before===$snapshot(),'Legacy rows changed');
 foreach(['users','categories','products','store_settings','service_orders','sales','expenses','number_sequences'] as $t){check(DB::table($t)->where('tenant_id','!=',1)->count()===0,'Backfill failed');check(!collect(Schema::getColumns($t))->firstWhere('name','tenant_id')['nullable'],'Nullable tenant');}
 check(Schema::hasIndex('number_sequences',['tenant_id','prefix','period'],'primary'),'Sequence key');
 foreach(['categories'=>['tenant_id','name'],'sales'=>['tenant_id','number'],'service_orders'=>['tenant_id','number'],'store_settings'=>['tenant_id']]as$t=>$cols)check(Schema::hasIndex($t,$cols,'unique'),'Unique key '.$t);
 check(Artisan::call('migrate:rollback',['--path'=>$tenant,'--realpath'=>true,'--force'=>true])===0,'Rollback failed');
 check(!Schema::hasTable('tenants')&&$before===$snapshot(),'Rollback changed data');
 check(Artisan::call('migrate',['--path'=>$tenant,'--realpath'=>true,'--force'=>true])===0,'Reapply failed');
 check($before===$snapshot(),'Reapply changed data');
 out(['case'=>'mysql-migration','database'=>$database,'legacy_tables'=>count($tables),'legacy_rows_preserved'=>true,'nonnullable_tenant_keys'=>true,'scoped_unique_keys'=>true,'rollback_roundtrip'=>true,'result'=>'pass']);
 $fixtures=[];$password=bin2hex(random_bytes(16));
 foreach(['A'=>1,'B'=>10]as$label=>$factor){
  $tenant=Tenant::create(['name'=>'Tenant S007 '.$label]);app(TenantContext::class)->set($tenant);
  $user=User::create(['username'=>'s007.'.strtolower($label),'name'=>'Owner S007 '.$label,'password'=>$password,'role'=>'admin']);auth()->login($user);
  $tech=User::create(['username'=>'s007.tech.'.strtolower($label),'name'=>'Teknisi S007 '.$label,'password'=>$password,'role'=>'teknisi']);
  $category=Category::create(['name'=>'Kategori S007 '.$label]);Category::create(['name'=>'Common category']);
  StoreSetting::create(['name'=>'Identitas S007 '.$label,'address'=>'Alamat S007 '.$label,'phone'=>'6281234567890','warranty_terms'=>'Garansi S007 '.$label]);
  $product=Product::create(['name'=>'Produk S007 '.$label,'cost_price'=>20000*$factor,'sell_price'=>100000*$factor,'stock'=>20]);
  $product->stockEntries()->create(['qty'=>20,'cost_price'=>20000*$factor,'entry_date'=>now()->toDateString(),'created_by'=>$user->id]);
  $race=Product::create(['name'=>'Race S007 '.$label,'cost_price'=>100,'sell_price'=>1000,'stock'=>1]);
  $order=app(CreateServiceOrder::class)->create(['customer_name'=>'Private customer S007 '.$label,'customer_phone'=>'081234567890','customer_address'=>'PRIVATE-ADDRESS-'.$label,'category_id'=>$category->id,'item_description'=>'Barang S007 '.$label,'complaint'=>'Keluhan S007 '.$label],$user);
  $order->update(['status'=>'selesai','technician_id'=>$tech->id,'invoiced_on'=>now()->toDateString(),'warranty_days'=>30,'warranty_until'=>now()->addDays(30)->toDateString()]);
  $order->items()->create(['type'=>'part','description'=>'Part S007 '.$label,'qty'=>1,'price'=>50000*$factor,'cost'=>10000*$factor]);
  $sale=app(CreateSale::class)->handle(['sold_on'=>now()->toDateString(),'customer_name'=>'Sale S007 '.$label,'warranty_days'=>'0','items'=>[['product_id'=>$product->id,'qty'=>1,'price'=>100000*$factor]]]);
  Expense::create(['spent_on'=>now()->toDateString(),'category'=>'bensin','description'=>'Biaya S007 '.$label,'amount'=>5000*$factor,'created_by'=>$user->id]);
  $fixtures[$label]=['tenant'=>$tenant->id,'user'=>$user->id,'username'=>$user->username,'tech'=>$tech->id,'category'=>$category->id,'product'=>$product->id,'race'=>$race->id,'order'=>$order->id,'sale'=>$sale->id,'token'=>$order->tracking_token,'factor'=>$factor];
 }
 file_put_contents($root.'/fixtures.json',json_encode(['password'=>$password,'shops'=>$fixtures]));chmod($root.'/fixtures.json',0600);
 out(['case'=>'fixtures','result'=>'pass','shops'=>2,'same_category_name_allowed'=>true]);
}elseif($mode==='number-worker'||$mode==='stock-worker'){
 $fixture=json_decode(file_get_contents($root.'/fixtures.json'),true)['shops'][$argv[2]];app(TenantContext::class)->set(Tenant::findOrFail($fixture['tenant']));auth()->login(User::findOrFail($fixture['user']));
 $start=(float)$argv[3];while(microtime(true)<$start)usleep(1000);$entered=microtime(true);
 if($mode==='number-worker'){
  $prefix=$argv[4];$number=DB::transaction(function()use($prefix){$number=app(DocumentNumber::class)->next($prefix,new Carbon\CarbonImmutable('2032-02-01', 'Asia/Makassar'));usleep(150000);return $number;},5);
  out(['result'=>'success','number'=>$number,'entered'=>$entered,'finished'=>microtime(true)]);
 }else{
  Product::updating(function($p)use($fixture){if($p->id===$fixture['race'])usleep(200000);});
  try{$sale=app(CreateSale::class)->handle(['sold_on'=>now()->toDateString(),'items'=>[['product_id'=>$fixture['race'],'qty'=>1,'price'=>1000]]]);out(['result'=>'success','sale'=>$sale->id]);}
  catch(ValidationException $e){check(isset($e->errors()['items.0.qty']),'Unexpected validation');out(['result'=>'shortage']);}
 }
}elseif($mode==='proof'||$mode==='number-proof'){
 $f=json_decode(file_get_contents($root.'/fixtures.json'),true)['shops'];
 foreach(['TTS','JL']as$prefix){$offset=[];foreach($f as$label=>$fixture)$offset[$label]=(int)DB::table('number_sequences')->where(['tenant_id'=>$fixture['tenant'],'prefix'=>$prefix,'period'=>'3202'])->value('last_seq');$start=(string)(microtime(true)+1);$workers=[];foreach(['A','B']as$label)for($i=0;$i<4;$i++){$p=new Process([PHP_BINARY,__FILE__,'number-worker',$label,$start,$prefix],getcwd(),['QA_RUNTIME'=>$root]);$p->start();$workers[]=[$label,$p];}
  $results=['A'=>[],'B'=>[]];foreach($workers as[$label,$p]){$p->wait();check($p->isSuccessful(),'Number child failed: '.$p->getErrorOutput());$results[$label][]=json_decode($p->getOutput(),true);}
  foreach($results as$label=>$rows){$numbers=array_column($rows,'number');sort($numbers);check($numbers===array_map(fn($n)=>$prefix.'-3202-'.str_pad($n,3,'0',STR_PAD_LEFT),range($offset[$label]+1,$offset[$label]+4)),'Numbers incorrect');check(max(array_column($rows,'entered'))<min(array_column($rows,'finished')),'No overlapping calls');}
  out(['case'=>'mysql-concurrent-numbers','prefix'=>$prefix,'results'=>$results,'result'=>'pass']);
 }
 if($mode==='number-proof')exit;
 foreach($f as$label=>$fixture){$start=(string)(microtime(true)+1);$workers=[];for($i=0;$i<2;$i++){$p=new Process([PHP_BINARY,__FILE__,'stock-worker',$label,$start],getcwd(),['QA_RUNTIME'=>$root]);$p->start();$workers[]=$p;}$results=[];foreach($workers as$p){$p->wait();check($p->isSuccessful(),'Stock child failed: '.$p->getErrorOutput());$results[]=json_decode($p->getOutput(),true);}$outcomes=array_column($results,'result');sort($outcomes);check($outcomes===['shortage','success'],'Race outcomes');check(DB::table('products')->where('id',$fixture['race'])->value('stock')===0,'Stock race count');out(['case'=>'mysql-stock-race','tenant'=>$label,'outcomes'=>$outcomes,'stock'=>0,'result'=>'pass']);}
 app(TenantContext::class)->set(Tenant::findOrFail($f['A']['tenant']));auth()->login(User::findOrFail($f['A']['user']));
 $before=['stock'=>Product::findOrFail($f['A']['product'])->stock,'sales'=>Sale::count(),'sequences'=>DB::table('number_sequences')->get()->toJson()];
 try{app(CreateSale::class)->handle(['sold_on'=>now()->toDateString(),'items'=>[['product_id'=>$f['A']['product'],'qty'=>1,'price'=>1000],['product_id'=>$f['B']['product'],'qty'=>1,'price'=>1000]]]);throw new RuntimeException('Foreign accepted');}catch(ValidationException $e){check(isset($e->errors()['items.1.product_id']),'Foreign error missing');}
 check($before===['stock'=>Product::findOrFail($f['A']['product'])->stock,'sales'=>Sale::count(),'sequences'=>DB::table('number_sequences')->get()->toJson()],'Mixed foreign payload changed data');
 out(['case'=>'mysql-foreign-payload-rollback','result'=>'pass','stock_sales_sequences_unchanged'=>true]);
 try{Artisan::call('migrate:rollback',['--path'=>database_path('migrations/2026_11_17_000000_add_tenant_foundation.php'),'--realpath'=>true,'--force'=>true]);throw new RuntimeException('Rollback accepted multiple tenants');}catch(LogicException $e){check(str_contains($e->getMessage(),'tenant lain'),'Unexpected rollback exception');}
 out(['case'=>'mysql-multitenant-rollback-guard','result'=>'pass']);
}elseif($mode==='details'){
 $f=json_decode(file_get_contents($root.'/fixtures.json'),true);
 foreach($f['shops']as$label=>&$fixture)$fixture['expense']=DB::table('expenses')->where('tenant_id',$fixture['tenant'])->value('id');unset($fixture);
 file_put_contents($root.'/fixtures.json',json_encode($f));chmod($root.'/fixtures.json',0600);
 $baseline=[];foreach(['users','categories','products','store_settings','service_orders','sales','expenses','number_sequences']as$t)$baseline[$t]=DB::table($t)->where('tenant_id',1)->get()->toArray();
 foreach(['service_order_items'=>'service_order_id','sale_items'=>'sale_id','stock_entries'=>'product_id']as$t=>$key)$baseline[$t]=DB::table($t)->where($key,1)->get()->toArray();
 file_put_contents($root.'/legacy-baseline',serialize($baseline));chmod($root.'/legacy-baseline',0600);out(['case'=>'legacy-baseline-before-final-browser','rows'=>array_map('count',$baseline),'result'=>'pass']);
}elseif($mode==='deactivate'){
 $f=json_decode(file_get_contents($root.'/fixtures.json'),true)['shops'];
 check(Tenant::whereKey($f['B']['tenant'])->update(['is_active'=>false])===1,'Deactivate');out(['case'=>'deactivate-synthetic-tenant-B','result'=>'pass']);
}elseif($mode==='verify'){
 $f=json_decode(file_get_contents($root.'/fixtures.json'),true)['shops'];$baseline=unserialize(file_get_contents($root.'/legacy-baseline'),['allowed_classes'=>['stdClass']]);
 foreach($baseline as$t=>$rows){$query=DB::table($t);$key=['service_order_items'=>'service_order_id','sale_items'=>'sale_id','stock_entries'=>'product_id'][$t]??'tenant_id';check($rows==$query->where($key,1)->get()->toArray(),'Legacy changed in '.$t);}
 foreach($f as$label=>$fixture){
  app(TenantContext::class)->set(Tenant::findOrFail($fixture['tenant']));auth()->login(User::findOrFail($fixture['user']));
  check(User::findOrFail($fixture['user'])->is_active,'Owner altered');check(User::findOrFail($fixture['tech'])->is_active,'Technician altered');check(Category::findOrFail($fixture['category'])->is_active,'Category altered');
  check(Hash::check(json_decode(file_get_contents($root.'/fixtures.json'),true)['password'],User::findOrFail($fixture['user'])->password),'Owner password altered');
  check(Product::findOrFail($fixture['product'])->name==='Produk S007 '.$label,'Product name altered');check(Product::findOrFail($fixture['race'])->stock===0,'Race stock changed');
  check(Product::findOrFail($fixture['product'])->stock===20,'Restock/sale stock incorrect');check(Sale::count()===3,'Unexpected sale count');check(ServiceOrder::count()===2,'Unexpected service order count');
  check(ServiceOrder::findOrFail($fixture['order'])->customer_name==='Private customer S007 '.$label,'Order customer altered');
  check(Expense::findOrFail($fixture['expense'])->amount===5000*$fixture['factor'],'Foreign expense mutated');
  check(!DB::table('service_orders')->where('customer_name','Forged customer')->exists(),'Foreign category created order');check(!DB::table('products')->where('name','Forged')->exists(),'Locked product changed');
  $revenue=app(App\Services\Reports\Report::class)->build(now()->toDateString(),now()->toDateString())['revenue'];check($revenue===(string)(250000*$fixture['factor']+1000),'Final report total');
  out(['case'=>'final-mysql-tenant-state','tenant'=>$label,'stock'=>Product::findOrFail($fixture['product'])->stock,'sales'=>Sale::count(),'service_orders'=>ServiceOrder::count(),'report_revenue'=>$revenue,'result'=>'pass']);
 }
 out(['case'=>'legacy-baseline-after-browser','identical_tables'=>count($baseline),'result'=>'pass']);
}else{throw new RuntimeException('Unknown mode');}

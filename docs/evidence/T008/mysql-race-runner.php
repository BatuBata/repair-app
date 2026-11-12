<?php
// Archived copy: actual execution was from the private task directory; nonempty-schema guard prevents blind replay.
$repo='/Users/muzib/orca/workspaces/repair-app/repair-t005-settings';chdir($repo);require 'vendor/autoload.php';
use Illuminate\Support\Facades\{Artisan,DB};
use App\Models\User;
$private=__DIR__;$out=$repo.'/docs/evidence/T008';$db='repair_app_qa_m1';
try {
 $values=Dotenv\Dotenv::parse(file_get_contents('/Users/muzib/Projects/web/repair-app/.env'));
 $host=$values['DB_HOST']??'127.0.0.1';if(!in_array($host,['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Non-loopback configuration refused');
 $pdo=new PDO('mysql:host=127.0.0.1;port='.($values['DB_PORT']??3306).';charset=utf8mb4',$values['DB_USERNAME'],$values['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $q=$pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');$q->execute([$db]);$exists=(bool)$q->fetchColumn();
 if(!$exists)$pdo->exec('CREATE DATABASE `repair_app_qa_m1` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');$q->execute([$db]);$count=(int)$q->fetchColumn();
 if($count!==0)throw new RuntimeException('QA schema nonempty before first migration; requires ownership review');
 $proof=['schema'=>$db,'existed_before'=>$exists,'tables_before'=>$count,'created_only_if_absent'=>!$exists,'version'=>$pdo->query('SELECT VERSION()')->fetchColumn(),'loopback_only'=>true,'started_at'=>gmdate('c')];
 $env=['APP_ENV'=>'testing','APP_KEY'=>'base64:'.base64_encode(random_bytes(32)),'APP_DEBUG'=>'false','DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>$values['DB_PORT']??'3306','DB_DATABASE'=>$db,'DB_USERNAME'=>$values['DB_USERNAME'],'DB_PASSWORD'=>$values['DB_PASSWORD'],'DB_URL'=>'','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','BCRYPT_ROUNDS'=>'4','LOG_CHANNEL'=>'single','LOG_LEVEL'=>'critical'];
 foreach($env as $k=>$v){putenv($k.'='.$v);$_ENV[$k]=$v;$_SERVER[$k]=$v;}
 $app=require 'bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
 $t=microtime(true);$code=Artisan::call('migrate',['--force'=>true,'--no-interaction'=>true]);if($code!==0)throw new RuntimeException('QA migration failed');
 $proof['migrate_exit']=$code;$proof['migrate_ms']=(microtime(true)-$t)*1000;
 $q=$pdo->prepare('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME');$q->execute([$db]);$proof['tables']=$q->fetchAll(PDO::FETCH_ASSOC);$proof['users_engine']=array_values(array_filter($proof['tables'],fn($r)=>$r['TABLE_NAME']==='users'))[0]['ENGINE'];
 file_put_contents($out.'/mysql-baseline.json',json_encode($proof,JSON_PRETTY_PRINT));
 $prefix='qa_t008_'.bin2hex(random_bytes(4));file_put_contents($private.'/mysql-ownership.json',json_encode(['schema'=>$db,'prefix'=>$prefix,'task'=>'T008','dispatch'=>'ctx_dd91a4362474','created_at'=>gmdate('c')]));chmod($private.'/mysql-ownership.json',0600);
 $cases=[];
 foreach([['deactivate','deactivate'],['demote','demote'],['deactivate','demote'],['demote','deactivate']] as $n=>$operations){
  // Only earlier task-owned synthetic rows are reset; never truncate/drop or touch another schema.
  User::where('username','like',$prefix.'%')->update(['is_active'=>false]);
  $admins=[];foreach([0,1] as $slot)$admins[]=User::factory()->create(['username'=>$prefix.'_'.$n.'_'.$slot,'name'=>'QA T008 race','role'=>'admin','is_active'=>true,'password'=>bin2hex(random_bytes(24))]);
  $barrier=$private.'/race-'.$n;mkdir($barrier,0700);$processes=[];
  foreach($operations as $slot=>$op){$p=new Symfony\Component\Process\Process([PHP_BINARY,$out.'/mysql-race-worker.php',(string)$admins[0]->id,(string)$admins[$slot]->id,$op,$barrier,(string)$slot],$repo,$env);$p->setTimeout(20);$p->start();$processes[]=$p;}
  $deadline=microtime(true)+12;while(!file_exists($barrier.'/ready-0')||!file_exists($barrier.'/ready-1')){if(microtime(true)>$deadline)throw new RuntimeException('QA barrier timed out');usleep(1000);}
  $released=microtime(true);touch($barrier.'/start');$workers=[];
  foreach($processes as $p){$p->wait();$r=json_decode($p->getOutput(),true);$workers[]=['exit'=>$p->getExitCode(),'result'=>$r??['outcome'=>'invalid_worker_output']];}
  $active=User::where('role','admin')->where('is_active',true)->count();$outcomes=array_column(array_column($workers,'result'),'outcome');$correct=count(array_filter($workers,fn($w)=>$w['exit']===0&&in_array($w['result']['outcome'],['succeeded','forbidden'],true)||($w['exit']===0&&$w['result']['outcome']==='protected'&&($w['result']['correct_constraint']??false))))===2;
  $cases[]=['case'=>'MYSQL-RACE-'.($n+1),'operations'=>$operations,'actor'=>'first admin for both workers; self target then peer target','barrier_release'=>$released,'synchronization'=>'ready files from two bootstrapped independent processes, one shared start file; 1ms poll','workers'=>$workers,'active_admins_after'=>$active,'verdict'=>$active===1&&count(array_filter($outcomes,fn($o)=>$o==='succeeded'))===1&&$correct?'pass':'fail'];
  file_put_contents($out.'/mysql-races.json',json_encode($cases,JSON_PRETTY_PRINT));
 }
 $inventory=['schema'=>$db,'ownership_prefix'=>$prefix,'users_total'=>User::count(),'active_admins'=>User::where('role','admin')->where('is_active',true)->count(),'task_owned_users'=>User::where('username','like',$prefix.'%')->count(),'preserved'=>true,'private_provenance'=>$private.'/mysql-ownership.json','scope'=>'Root credentials used only in memory/process environment for local isolated QA; not production least-privilege acceptance'];file_put_contents($out.'/mysql-inventory.json',json_encode($inventory,JSON_PRETTY_PRINT));
 echo json_encode(['baseline'=>'empty','engine'=>$proof['users_engine'],'cases'=>array_column($cases,'verdict'),'preserved_users'=>$inventory['users_total']])."\n";
} catch(Throwable $e){echo json_encode(['outcome'=>'blocked','class'=>get_class($e),'code'=>$e->getCode(),'safe_reason'=>$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'private connection/bootstrap failure; secrets withheld'])."\n";exit(1);}

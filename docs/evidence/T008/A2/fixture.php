<?php
$repo=getcwd();require $repo.'/vendor/autoload.php';$app=require $repo.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User,Category,StoreSetting};
$mode=$argv[1];
if($mode==='init') {
 foreach(['admin','teknisi','controller'] as $role) User::factory()->create(['username'=>'t008_'.$role,'name'=>'Sintetis T008 '.ucfirst($role),'role'=>$role==='teknisi'?'teknisi':'admin','password'=>getenv('QA_PASSWORD')]);
 Category::create(['name'=>'Televisi']); Category::create(['name'=>'Laptop']);
 StoreSetting::create(['id'=>1,'name'=>'Toko Sintetis T008','address'=>'Alamat fixture lokal','phone'=>'081234567890','default_warranty_days'=>30,'warranty_terms'=>'Garansi fixture']);
 echo json_encode(['driver'=>config('database.default'),'users'=>User::count(),'categories'=>Category::count(),'settings'=>StoreSetting::count(),'private_storage'=>str_starts_with(storage_path(),'/private/tmp/t008-a2-')])."\n";
} elseif($mode==='grant') {
 auth()->login(User::where('username','t008_admin')->firstOrFail());
 $c=new App\Livewire\Settings\Store; $c->boot();$c->_startUpload('logo',[['name'=>'independent.png','size'=>1000,'type'=>'image/png']],false);
 $events=Livewire\store($c)->get('dispatched');$url=$events[0]->serialize()['params']['url'];
 $path=getenv('QA_RUNTIME').'/grant.json';file_put_contents($path,json_encode(['url'=>$url]));chmod($path,0600);
 echo "Actual Store::_startUpload grant saved privately.\n";
} elseif($mode==='transition') {
 auth()->login(User::where('username','t008_controller')->firstOrFail());
 $c=new App\Livewire\Users\Index; $u=User::where('username','t008_admin')->firstOrFail();$c->edit($u->id);
 if($argv[2]==='demote')$c->role='teknisi';
 if($argv[2]==='restore'){$c->role='admin';$c->is_active=true;}
 if($argv[2]==='deactivate')$c->is_active=false;
 $c->save();$u->refresh();echo json_encode(['action'=>'Users::edit/save','role'=>$u->role->value,'active'=>$u->is_active])."\n";
} else {throw new RuntimeException('Unknown fixture action');}

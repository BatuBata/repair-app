<?php
// QA-only worker, invoked with inherited private process environment and QA DB guard.
use App\Livewire\Users\Index;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
if (config('database.default')!=='mysql'||config('database.connections.mysql.database')!=='repair_app_qa_m1') exit(91);
[$script,$actor,$target,$operation,$barrier,$slot]=$argv;
auth()->loginUsingId($actor); $component=new Index;
$queries=0;$queryMs=0;$attempts=0;
DB::listen(function($event)use(&$queries,&$queryMs){$queries++;$queryMs+=$event->time;});
$app['events']->listen(Illuminate\Database\Events\TransactionBeginning::class,function()use(&$attempts){$attempts++;});
touch($barrier.'/ready-'.$slot); $deadline=microtime(true)+10;
while(!file_exists($barrier.'/start')){if(microtime(true)>$deadline)exit(92);usleep(1000);}
$start=microtime(true);$result=['actor'=>(int)$actor,'target'=>(int)$target,'operation'=>$operation,'start'=>$start];
try {
 if($operation==='deactivate')$component->toggleActive($target);
 else {$component->edit($target);$component->role='teknisi';$component->save();}
 $result['outcome']='succeeded';
} catch(ValidationException $e) {$result['outcome']='protected';$result['correct_constraint']=($e->errors()['is_active'][0]??'')==='Minimal harus ada satu admin aktif.';}
catch(HttpException $e){$result['outcome']=$e->getStatusCode()===403?'forbidden':'unexpected_http';$result['status']=$e->getStatusCode();}
catch(Throwable $e){$result['outcome']='environment_or_unexpected_error';$result['class']=get_class($e);$result['code']=$e->getCode();}
$result+=['end'=>microtime(true),'duration_ms'=>(microtime(true)-$start)*1000,'queries'=>$queries,'query_ms'=>$queryMs,'transaction_attempts'=>$attempts];
echo json_encode($result);

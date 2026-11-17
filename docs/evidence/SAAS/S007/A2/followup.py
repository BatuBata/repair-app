"""Complete only unproven guards/static checks; do not repeat the successful MariaDB test."""
import json,os,pathlib,subprocess,tempfile,shutil,sys
ROOT=pathlib.Path.cwd();OUT=ROOT/'docs/evidence/SAAS/S007/A2';PHP='/opt/homebrew/opt/php@8.3/bin/php'
def run(args,**kw):return subprocess.run(args,cwd=ROOT,text=True,capture_output=True,**kw)
results=[]
for driver,database in ([] if '--static-only' in sys.argv else [('sqlite',':memory:'),('mysql','saas_s007_forbidden')]):
 env={**os.environ,'APP_ENV':'testing','DB_CONNECTION':driver,'DB_DATABASE':database,'DB_HOST':'127.0.0.1','DB_PORT':'1','DB_USERNAME':'not-used','DB_PASSWORD':'','DB_URL':''}
 r=run([PHP,'tests/Feature/Domain/mysql-worker.php','TTS','0'],env=env)
 assert 'Only repair_app_test on MySQL is permitted' in r.stdout+r.stderr,'Guard message missing'
 try:json.loads(r.stdout);raise AssertionError('Unexpected JSON success')
 except json.JSONDecodeError:pass
 results.append({'driver':driver,'database':database,'exit_code':r.returncode,'guard_message_observed':True,'valid_worker_json':False,'database_server_needed':False,'reason':'Guard executes before Tenant::findOrFail or any SQL; parent rejects invalid child JSON even though Laravel exception renderer returns zero.'})
if results:(OUT/'worker-guards.json').write_text(json.dumps(results,indent=2))
root=pathlib.Path(tempfile.mkdtemp(prefix='s007-a2-static-',dir='/private/tmp'));static={}
try:
 for label,revision in [('baseline','dec7804'),('candidate','6d0254c')]:
  tree=root/label;folder=tree/'tests/Feature/Domain';folder.mkdir(parents=True)
  for part in ['vendor','bootstrap']:(tree/part).symlink_to(ROOT/part,target_is_directory=True)
  paths=[]
  for name in ['MySqlConcurrencyTest.php','mysql-worker.php']:
   content=run(['git','show',revision+':tests/Feature/Domain/'+name]);assert content.returncode==0;path=folder/name;path.write_text(content.stdout);paths.append(str(path))
  check=run([PHP,'vendor/bin/phpstan','analyse','--debug','--memory-limit=512M','--error-format=json','--no-progress',*paths]);(OUT/('phpstan-'+label+'.json')).write_text(check.stdout);parsed=json.loads(check.stdout)
  if 'files' in parsed:messages=sorted(e['message']for f in parsed['files'].values()for e in f['messages'])
  else:messages=sorted(e['message']for f in parsed.get('error_details',{}).values()for e in f)
  assert 'files'in parsed or 'error_details'in parsed,'No usable static result'
  static[label]={'exit_code':check.returncode,'messages':messages,'errors':len(messages)}
 assert set(static['baseline']['messages'])==set(static['candidate']['messages']),'New targeted static diagnostic class'
 (OUT/'static-comparison.json').write_text(json.dumps(static,indent=2))
finally:shutil.rmtree(root)
print('Wrong-driver/database guards proven; targeted static baseline comparison complete.')

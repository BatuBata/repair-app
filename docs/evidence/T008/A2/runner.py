import os,pathlib,json,subprocess,sys,time,signal
repo=pathlib.Path.cwd(); out=repo/'docs/evidence/T008/A2'; runtime=pathlib.Path('/private/tmp/t008-a2-location').read_text().strip(); root=pathlib.Path(runtime)
vals=json.loads((root/'private.json').read_text()); env=os.environ.copy();env.update(vals)
env.update(QA_RUNTIME=runtime,LARAVEL_STORAGE_PATH=str(root/'storage'),APP_CONFIG_CACHE=str(root/'config.php'),APP_ENV='local',APP_DEBUG='false',APP_URL='http://127.0.0.1:8188',DB_CONNECTION='sqlite',DB_URL='',CACHE_STORE='array',SESSION_DRIVER='file',QUEUE_CONNECTION='sync',MAIL_MAILER='array',LOG_CHANNEL='single',LOG_LEVEL='error',BCRYPT_ROUNDS='4',RUN_MYSQL_DOMAIN='0')
php='/opt/homebrew/opt/php@8.3/bin/php';mode=sys.argv[1]
def run(args,label):
 start=time.time();p=subprocess.run([php]+args,cwd=repo,env=env,capture_output=True,text=True);output=p.stdout+p.stderr
 for v in vals.values():
  if len(v)>8: output=output.replace(v,'[PRIVATE]')
 (out/(label+'.log')).write_text('\n'.join(x.rstrip() for x in output.splitlines()).rstrip()+'\n');(out/(label+'-execution.json')).write_text(json.dumps({'command':[php]+args,'exit':p.returncode,'duration_s':time.time()-start},indent=2)+'\n');print(label,p.returncode);return p.returncode
if mode=='init':
 db=pathlib.Path(vals['DB_DATABASE']);assert not db.exists();db.touch(mode=0o600)
 assert run(['artisan','migrate','--force','--no-interaction','--no-ansi'],'fixture-migrate')==0
 sys.exit(run(['docs/evidence/T008/A2/fixture.php','init'],'fixture-proof'))
if mode=='fixture': sys.exit(run(['docs/evidence/T008/A2/fixture.php']+sys.argv[2:],'fixture-'+'-'.join(sys.argv[2:])))
if mode=='cache': sys.exit(run(['artisan','config:cache','--no-ansi'],'private-config-cache'))
if mode=='targeted':
 env.update(APP_ENV='testing',DB_DATABASE=':memory:',SESSION_DRIVER='array',APP_CONFIG_CACHE=str(root/'testing-config-absent.php'))
 sys.exit(run(['vendor/bin/phpunit','--filter','AuthenticationTest|RoleLauncherTest|TemporaryUploadAuthorizationTest|SettingsTest','--log-junit=docs/evidence/T008/A2/targeted.xml','--colors=never'],'targeted'))
if mode=='serve':
 os.execvpe(php,[php,'artisan','serve','--host=127.0.0.1','--port=8188','--no-reload','--no-ansi'],env)

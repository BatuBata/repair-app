import os,pathlib,tempfile,secrets,json,base64,subprocess,sys
repo=pathlib.Path.cwd(); out=repo/'docs/evidence/T016/A2'; pointer=pathlib.Path('/private/tmp/t016-a2-location'); php='/opt/homebrew/opt/php@8.3/bin/php'
if sys.argv[1]=='init':
 root=pathlib.Path(tempfile.mkdtemp(prefix='t016-a2-',dir='/private/tmp'));root.chmod(0o700);pointer.write_text(str(root));pointer.chmod(0o600)
 for p in ['storage/framework/sessions','storage/framework/views','storage/framework/cache/data','storage/logs','storage/app/private','storage/app/public','chrome']:(root/p).mkdir(parents=True,exist_ok=True)
 env=dict(QA_PASSWORD=secrets.token_urlsafe(24),APP_KEY='base64:'+base64.b64encode(secrets.token_bytes(32)).decode(),DB_DATABASE=str(root/'fixture.sqlite'),QA_RUNTIME=str(root),LARAVEL_STORAGE_PATH=str(root/'storage'),APP_CONFIG_CACHE=str(root/'config.php'),APP_ENV='local',APP_DEBUG='false',APP_URL='http://127.0.0.1:8189',DB_CONNECTION='sqlite',DB_URL='',CACHE_STORE='array',SESSION_DRIVER='file',QUEUE_CONNECTION='sync',MAIL_MAILER='array',LOG_CHANNEL='single',LOG_LEVEL='error',BCRYPT_ROUNDS='4',RUN_MYSQL_DOMAIN='0')
 (root/'private.json').write_text(json.dumps(env));(root/'private.json').chmod(0o600);(root/'fixture.sqlite').touch(mode=0o600)
root=pathlib.Path(pointer.read_text().strip());env={**os.environ,**json.loads((root/'private.json').read_text())}
if sys.argv[1]=='serve':os.execvpe(php,[php,'artisan','serve','--host=127.0.0.1','--port=8189','--no-reload','--no-ansi'],env)
for mode in ['config','seed'] if sys.argv[1]=='init' else [sys.argv[1]]:
 p=subprocess.run([php,'docs/evidence/T016/A2/fixture.php',mode],env=env,capture_output=True,text=True)
 text=p.stdout+p.stderr
 for secret in [env['APP_KEY'],env['QA_PASSWORD']]:text=text.replace(secret,'[PRIVATE]')
 (out/('fixture-'+mode+'.log')).write_text(text);print(mode,p.returncode);assert p.returncode==0

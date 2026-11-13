import os, pathlib, json, subprocess, sys, tempfile, secrets
repo=pathlib.Path.cwd(); out=repo/'docs/evidence/T012/A1'; pointer=pathlib.Path('/private/tmp/t012-a1-location'); php='/opt/homebrew/opt/php@8.3/bin/php'
mode=sys.argv[1]
if mode=='init':
    root=pathlib.Path(tempfile.mkdtemp(prefix='t012-a1-',dir='/private/tmp')); root.chmod(0o700); pointer.write_text(str(root)); pointer.chmod(0o600)
    for p in ['storage/framework/sessions','storage/framework/views','storage/framework/cache/data','storage/logs','storage/app/private','storage/app/public','chrome']:(root/p).mkdir(parents=True,exist_ok=True)
    private={'QA_PASSWORD':secrets.token_urlsafe(24),'APP_KEY':'base64:'+__import__('base64').b64encode(secrets.token_bytes(32)).decode(),'DB_DATABASE':str(root/'fixture.sqlite')}
    (root/'private.json').write_text(json.dumps(private));(root/'private.json').chmod(0o600);(root/'fixture.sqlite').touch(mode=0o600)
root=pathlib.Path(pointer.read_text().strip()); private=json.loads((root/'private.json').read_text()); env=os.environ.copy();env.update(private)
env.update(QA_RUNTIME=str(root),LARAVEL_STORAGE_PATH=str(root/'storage'),APP_CONFIG_CACHE=str(root/'config.php'),APP_ENV='local',APP_DEBUG='false',APP_URL='http://127.0.0.1:8190',DB_CONNECTION='sqlite',DB_URL='',CACHE_STORE='array',SESSION_DRIVER='file',QUEUE_CONNECTION='sync',MAIL_MAILER='array',LOG_CHANNEL='single',LOG_LEVEL='error',BCRYPT_ROUNDS='4',RUN_MYSQL_DOMAIN='0')
def run(args,label):
    result=subprocess.run([php]+args,cwd=repo,env=env,capture_output=True,text=True)
    output=result.stdout+result.stderr
    for value in private.values():output=output.replace(value,'[PRIVATE]')
    (out/(label+'.log')).write_text('\n'.join(line.rstrip() for line in output.splitlines()).rstrip()+'\n');print(label,result.returncode);assert result.returncode==0
if mode=='init':
    run(['artisan','migrate','--force','--no-interaction','--no-ansi'],'fixture-migrate')
    run(['artisan','config:cache','--no-ansi'],'private-config-cache')
    run(['docs/evidence/T012/A1/fixture.php','init'],'fixture-init')
elif mode=='fixture':run(['docs/evidence/T012/A1/fixture.php',sys.argv[2]],'fixture-'+sys.argv[2])
elif mode=='serve':os.execvpe(php,[php,'artisan','serve','--host=127.0.0.1','--port=8190','--no-reload','--no-ansi'],env)

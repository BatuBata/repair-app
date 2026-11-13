from pathlib import Path
import tempfile,os,json,hashlib,subprocess,secrets,time
root=Path.cwd();out=root/'docs/evidence/T023/A1';tmp=Path(tempfile.mkdtemp(prefix='t023-a1-',dir='/private/tmp'));os.chmod(tmp,0o700)
for d in ['storage/framework/cache/data','storage/framework/cache/locks','storage/framework/sessions','storage/framework/views','storage/logs','storage/app/private','storage/app/public','fonts','chrome','bootstrap']:(tmp/d).mkdir(parents=True,exist_ok=True)
(tmp/'fixture.sqlite').touch()
env={'QA_RUNTIME':str(tmp),'QA_PASSWORD':secrets.token_urlsafe(20),'APP_ENV':'testing','APP_DEBUG':'false','APP_URL':'http://127.0.0.1:8193','APP_KEY':'base64:'+__import__('base64').b64encode(secrets.token_bytes(32)).decode(),'DB_CONNECTION':'sqlite','DB_DATABASE':str(tmp/'fixture.sqlite'),'DB_URL':'','RUN_MYSQL_DOMAIN':'0','CACHE_STORE':'file','SESSION_DRIVER':'file','SESSION_COOKIE':'t023_session','SESSION_SECURE_COOKIE':'false','MAIL_MAILER':'array','BCRYPT_ROUNDS':'4','LARAVEL_STORAGE_PATH':str(tmp/'storage'),'VIEW_COMPILED_PATH':str(tmp/'storage/framework/views'),'APP_CONFIG_CACHE':str(tmp/'bootstrap/config.php'),'APP_ROUTES_CACHE':str(tmp/'bootstrap/routes.php'),'APP_EVENTS_CACHE':str(tmp/'bootstrap/events.php'),'APP_SERVICES_CACHE':str(tmp/'bootstrap/services.php'),'APP_PACKAGES_CACHE':str(tmp/'bootstrap/packages.php')}
(tmp/'private.json').write_text(json.dumps(env));os.chmod(tmp/'private.json',0o600);Path('/private/tmp/t023-a1-location').write_text(str(tmp));os.chmod('/private/tmp/t023-a1-location',0o600)
proof={'checked_revision':subprocess.check_output(['git','rev-parse','HEAD'],text=True).strip(),'branch':subprocess.check_output(['git','branch','--show-current'],text=True).strip(),'runtime':str(tmp),'manifests':{}}
for name in ['source','build','font']:
 rows=json.loads((root/f'docs/evidence/T022/A1/{name}-manifest.json').read_text());bad=[]
 for row in rows:
  p=root/row['path'];h=hashlib.sha256(p.read_bytes()).hexdigest() if p.exists() else 'MISSING'
  if h!=row['sha256']:bad.append(row['path'])
 proof['manifests'][name]={'files':len(rows),'mismatches':bad,'manifest_sha256':hashlib.sha256((root/f'docs/evidence/T022/A1/{name}-manifest.json').read_bytes()).hexdigest()}
assert all(not x['mismatches'] for x in proof['manifests'].values());(out/'input-proof.json').write_text(json.dumps(proof,indent=2));print(json.dumps(proof,indent=2))

import os,json,subprocess,time
from pathlib import Path
root=Path.cwd();out=root/'docs/evidence/T023/A1';tmp=Path('/private/tmp/t023-a1-location').read_text().strip();env={**os.environ,**json.loads(Path(tmp+'/private.json').read_text()),'DB_DATABASE':':memory:','CACHE_STORE':'array','SESSION_DRIVER':'array','APP_CONFIG_CACHE':tmp+'/bootstrap/suite-config.php'};php='/opt/homebrew/opt/php@8.3/bin/php';rows=[]
for name,cmd in [('full-suite',[php,'artisan','test','--no-ansi','--log-junit',str(out/'suite.xml')]),('pint',[php,'vendor/bin/pint','--test']),('phpstan',[php,'vendor/bin/phpstan','analyse','--memory-limit=512M','--no-progress'])]:
 start=time.monotonic()
 with (out/(name+'.log')).open('w') as f:r=subprocess.run(cmd,cwd=root,env=env,stdout=f,stderr=subprocess.STDOUT)
 row={'id':name,'command':cmd,'exit':r.returncode,'seconds':round(time.monotonic()-start,3),'environment':'privateSQLite :memory: DB_URL empty RUN_MYSQL_DOMAIN0 CACHE_STORE array SESSION_DRIVER array private config/storage/views'};rows.append(row);(out/'checks.json').write_text(json.dumps(rows,indent=2));print(json.dumps(row),flush=True)

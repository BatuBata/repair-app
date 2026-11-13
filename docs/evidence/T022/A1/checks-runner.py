import os,json,subprocess,time
from pathlib import Path
root=Path('/Users/muzib/Projects/web/repair-app');p=Path('/private/tmp/t022-a1-location').read_text().strip();out=root/'docs/evidence/T022/A1';env={**os.environ,**json.loads(Path(p+'/private.json').read_text()),'DB_DATABASE':':memory:'};php='/opt/homebrew/opt/php@8.3/bin/php';rows=[]
checks=[('pint',[php,'vendor/bin/pint','--test']),('phpstan',[php,'vendor/bin/phpstan','analyse','--memory-limit=512M','--no-progress']),('full-suite',[php,'artisan','test','--no-ansi']),('build',['npm','run','build']),('ops-syntax',['bash','-n','deploy/backup.sh']),('restore-syntax',['bash','-n','deploy/restore.sh']),('deploy-syntax',['bash','-n','deploy/deploy.sh'])]
for name,cmd in checks:
 start=time.monotonic()
 with (out/(name+'.log')).open('w') as f:r=subprocess.run(cmd,cwd=root,env=env,stdout=f,stderr=subprocess.STDOUT)
 row={'id':name,'command':cmd,'exit':r.returncode,'seconds':round(time.monotonic()-start,3),'environment':'private paths; sqlite :memory: DB_URL empty RUN_MYSQL_DOMAIN0'};rows.append(row);(out/'checks.json').write_text(json.dumps(rows,indent=2));print(json.dumps(row),flush=True)

exec(open('/private/tmp/t022-check.py').read().split('checks=')[0])
env.update({'CACHE_STORE':'array','SESSION_DRIVER':'array'})
for name,cmd in [('pint-final',[php,'vendor/bin/pint','--test']),('full-suite-final',[php,'artisan','test','--no-ansi'])]:
 start=time.monotonic()
 with (out/(name+'.log')).open('w') as f:r=subprocess.run(cmd,cwd=root,env=env,stdout=f,stderr=subprocess.STDOUT)
 row={'id':name,'command':cmd,'exit':r.returncode,'seconds':round(time.monotonic()-start,3),'environment':'SQLite memory DB_URL empty RUN_MYSQL_DOMAIN0 CACHE_STORE array SESSION_DRIVER array private storage/cache/view/config paths'};rows.append(row);print(json.dumps(row),flush=True)
(out/'checks-final.json').write_text(json.dumps(rows,indent=2))

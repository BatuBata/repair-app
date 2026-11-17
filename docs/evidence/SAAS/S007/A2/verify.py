"""Independent targeted MariaDB QA. Secrets are generated only in the private runtime."""
import hashlib,json,os,pathlib,secrets,subprocess,tempfile,time

ROOT=pathlib.Path.cwd(); OUT=ROOT/'docs/evidence/SAAS/S007/A2'
PHP='/opt/homebrew/opt/php@8.3/bin/php'; DOCKER=['docker','--context','colima']
RUNTIME=pathlib.Path(tempfile.mkdtemp(prefix='s007-a2-private-',dir='/private/tmp'));RUNTIME.chmod(0o700)
NAME='repair-s007-a2-'+secrets.token_hex(5);PASSWORD=secrets.token_hex(24);ADMIN=secrets.token_hex(24)
def run(args,**kwargs):return subprocess.run(args,cwd=ROOT,text=True,capture_output=True,**kwargs)
def docker(*args,**kwargs):return run(DOCKER+list(args),**kwargs)
def save(name,data):(OUT/name).write_text(json.dumps(data,indent=2))
def query(sql):
 r=docker('exec','--env','MYSQL_PWD',NAME,'mariadb','--user=root','--batch','--skip-column-names','--execute',sql,env={**os.environ,'MYSQL_PWD':PASSWORD});assert r.returncode==0,'Private SQL command failed';return r.stdout.strip()
container_id=None
try:
 before=docker('ps','-a','--format','{{.ID}} {{.Image}} {{.Names}} {{.Status}}');assert before.returncode==0;save('docker-before.json',{'containers':before.stdout.splitlines()})
 envfile=RUNTIME/'maria.env';envfile.write_text('MARIADB_ROOT_PASSWORD='+PASSWORD+'\nMARIADB_DATABASE=repair_app_test\n');envfile.chmod(0o600)
 started=docker('run','--detach','--name',NAME,'--label','task=S007-A2','--env-file',str(envfile),'--tmpfs','/var/lib/mysql:rw','--publish','127.0.0.1::3306','mariadb:10.11.14');assert started.returncode==0,started.stderr;container_id=started.stdout.strip()
 for _ in range(40):
  if docker('exec',NAME,'healthcheck.sh','--connect','--innodb_initialized').returncode==0:break
  time.sleep(.5)
 else:raise RuntimeError('Owned MariaDB readiness timeout')
 mapping=docker('port',NAME,'3306/tcp');assert mapping.returncode==0;host,port=mapping.stdout.strip().split(':');assert host=='127.0.0.1'
 version=query('SELECT VERSION()');assert version=='10.11.14-MariaDB-ubu2204',version
 tables_before=query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='repair_app_test'");assert tables_before=='0'
 save('mariadb-before.json',{'container_id':container_id,'name':NAME,'task_label':'S007-A2','version':version,'image_tag':'mariadb:10.11.14','image_id':docker('image','inspect','mariadb:10.11.14','--format','{{.Id}}').stdout.strip(),'host':host,'port':port,'database':'repair_app_test','initial_tables':0,'storage':'new owned container tmpfs, no host volume'})
 local=RUNTIME/'test.env';local.write_text(f'DB_HOST={host}\nDB_PORT={port}\nDB_USERNAME=root\nDB_PASSWORD={PASSWORD}\nADMIN_INITIAL_PASSWORD={ADMIN}\n');local.chmod(0o600)
 env={**os.environ,'APP_ENV':'testing','APP_KEY':'base64:'+__import__('base64').b64encode(secrets.token_bytes(32)).decode(),'RUN_MYSQL_DOMAIN':'1','REPAIR_MYSQL_ENV_FILE':str(local),'T002_MYSQL_DIAGNOSTICS':str(OUT/'mysql-timings.jsonl')}
 command=[PHP,'vendor/bin/phpunit','--filter','MySqlConcurrencyTest','--colors=never'];r=run(command,env=env);(OUT/'mariadb-test.log').write_text(r.stdout+r.stderr);assert r.returncode==0,'Targeted MariaDB test failed'
 counts=query("SELECT (SELECT COUNT(*) FROM repair_app_test.tenants),(SELECT COUNT(*) FROM repair_app_test.users WHERE tenant_id=1 AND username='admin'),(SELECT COUNT(*) FROM repair_app_test.service_orders WHERE tenant_id=1),(SELECT COUNT(*) FROM repair_app_test.sales WHERE tenant_id=1),(SELECT COUNT(DISTINCT tracking_token) FROM repair_app_test.service_orders),(SELECT COUNT(*) FROM repair_app_test.categories WHERE tenant_id=1)")
 assert counts=='1\t1\t8\t8\t8\t9',counts
 sequences=query('SELECT tenant_id,prefix,period,last_seq FROM repair_app_test.number_sequences ORDER BY prefix');assert sequences=='1\tJL\t2611\t8\n1\tTTS\t2611\t8',sequences
 numbers={table:query(f'SELECT number FROM repair_app_test.{table} ORDER BY number').splitlines()for table in ['service_orders','sales']}
 assert numbers['service_orders']==[f'TTS-2611-{i:03}'for i in range(1,9)]
 assert numbers['sales']==[f'JL-2611-{i:03}'for i in range(1,9)]
 rows=[json.loads(line)for line in (OUT/'mysql-timings.jsonl').read_text().splitlines()];assert len(rows)==4 and sum(len(x['workers'])for x in rows)==16
 for row in rows:assert max(x['entered']for x in row['workers'])<min(x['finished']for x in row['workers'])
 save('mariadb-proof.json',{'verdict':'pass','command':command,'exit_code':r.returncode,'version':version,'tenant1_seeded':True,'counts':{'tenants':1,'admin_tenant1':1,'tts':8,'sales':8,'distinct_tracking_tokens':8,'categories':9},'number_sequences':sequences.splitlines(),'numbers':numbers,'overlapping_rounds':4,'child_writes':16})
 def inventory():
  names=query("SELECT table_name FROM information_schema.tables WHERE table_schema='repair_app_test' ORDER BY table_name").splitlines()
  return {name:hashlib.sha256(query(f'SELECT * FROM repair_app_test.`{name}`').encode()).hexdigest()for name in names}
 initial=inventory();negative=run(command,env=env);(OUT/'nonempty-guard.log').write_text(negative.stdout+negative.stderr);assert negative.returncode!=0 and 'test database must be empty' in negative.stdout;assert inventory()==initial
 wrongenv={**env,'DB_CONNECTION':'mysql','DB_HOST':host,'DB_PORT':port,'DB_USERNAME':'root','DB_PASSWORD':PASSWORD,'DB_DATABASE':'saas_s007_guard','DB_URL':''}
 query('CREATE DATABASE saas_s007_guard')
 worker=run([PHP,'tests/Feature/Domain/mysql-worker.php','TTS','0'],env=wrongenv);assert 'Only repair_app_test on MySQL is permitted' in worker.stdout+worker.stderr
 try:json.loads(worker.stdout);raise AssertionError('Guard output must not be successful worker JSON')
 except json.JSONDecodeError:pass
 assert query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='saas_s007_guard'")=='0'
 save('guards.json',{'nonempty_parent_refused':True,'all_existing_owned_rows_after_refusal_identical':True,'wrong_database_worker_refused':True,'wrong_database_tables_after':0,'host_mysql_or_other_containers_used':False})
finally:
 if container_id:
  inspected=docker('inspect',NAME,'--format','{{.Id}} {{index .Config.Labels "task"}}');assert inspected.returncode==0 and inspected.stdout.strip()==container_id+' S007-A2','Cleanup owner guard'
  removed=docker('rm','--force',NAME);assert removed.returncode==0
 after=docker('ps','-a','--format','{{.ID}} {{.Image}} {{.Names}} {{.Status}}')
 # No secret ever appears in published text evidence.
 leaks=[]
 for file in OUT.rglob('*'):
  if file.is_file() and any(secret in file.read_text(errors='replace')for secret in [PASSWORD,ADMIN,env.get('APP_KEY','')if 'env'in locals()else secrets.token_hex(32)]):leaks.append(file.name)
 assert not leaks,leaks
 import shutil
 shutil.rmtree(RUNTIME)
 save('cleanup.json',{'owned_container_removed':bool(container_id),'tmpfs_database_removed':bool(container_id),'private_runtime_removed':True,'other_containers_after':after.stdout.splitlines(),'secrets_in_evidence':False,'existing_mysql_host_connected':False})
print('Independent targeted MariaDB and guards complete; own container/runtime removed.')

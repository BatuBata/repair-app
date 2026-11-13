import os,sys,pathlib,tempfile,json,subprocess,time
repo=pathlib.Path.cwd();out=repo/'docs/evidence/T013/A1';pointer=pathlib.Path('/private/tmp/t013-mysql-location');php='/opt/homebrew/opt/php@8.3/bin/php';script='tests/Feature/Sales/mysql-concurrency.php';mode=sys.argv[1]
if mode=='inventory':
 root=pathlib.Path(tempfile.mkdtemp(prefix='t013-mysql-',dir='/private/tmp'));root.chmod(0o700);pointer.write_text(str(root));pointer.chmod(0o600)
else:root=pathlib.Path(pointer.read_text().strip())
env=os.environ.copy();env.update(QA_RUNTIME=str(root),APP_CONFIG_CACHE=str(root/'absent-config.php'))
def run(args):
 p=subprocess.run([php,script]+args,cwd=repo,env=env,capture_output=True,text=True)
 (root/('-'.join(args)+'.stderr')).write_text(p.stderr);(root/('-'.join(args)+'.stderr')).chmod(0o600)
 if p.returncode:print(json.dumps({'phase':args,'exit':p.returncode,'error_saved_privately':True}));sys.exit(p.returncode)
 try:d=json.loads(p.stdout)
 except ValueError:print('Non-JSON output saved privately');(root/'unexpected-output').write_text(p.stdout);sys.exit(1)
 (out/('mysql-'+'-'.join(args)+'.json')).write_text(json.dumps(d,indent=2)+'\n');print(json.dumps(d));return d
if mode=='inventory':run(['inventory'])
elif mode=='race':
 run(['init']);processes=[subprocess.Popen([php,script,'worker',str(i)],cwd=repo,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True) for i in range(2)]
 deadline=time.time()+15
 while not all((root/('ready-'+str(i))).exists() for i in range(2)):
  if time.time()>deadline:raise RuntimeError('Finite startup barrier timed out; workers not released')
  time.sleep(.02)
 (root/'go').touch()
 for i,p in enumerate(processes):
  stdout,stderr=p.communicate(timeout=20);(root/('worker-'+str(i)+'.stderr')).write_text(stderr)
  if p.returncode:raise RuntimeError('Worker failed; diagnostics retained privately')
  d=json.loads(stdout);(out/('mysql-worker-'+str(i)+'.json')).write_text(json.dumps(d,indent=2)+'\n');print(json.dumps(d))
 run(['proof'])

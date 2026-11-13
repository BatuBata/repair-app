import os,sys,pathlib,tempfile,subprocess,json,time
repo=pathlib.Path.cwd();out=repo/'docs/evidence/T016/A1';root=pathlib.Path(tempfile.mkdtemp(prefix='t016-mysql-',dir='/private/tmp'));root.chmod(0o700);pathlib.Path('/private/tmp/t016-mysql-location').write_text(str(root));env={**os.environ,'QA_RUNTIME':str(root),'APP_CONFIG_CACHE':str(root/'absent.php')};php='/opt/homebrew/opt/php@8.3/bin/php';args=[php,'docs/evidence/T016/A1/mysql.php']
def collect(p,label):
 stdout,stderr=p.communicate(timeout=25);(root/(label+'.stdout')).write_text(stdout);(root/(label+'.stderr')).write_text(stderr)
 for path in root.glob('*'):path.chmod(0o600)
 if p.returncode:raise RuntimeError(label+' failed; private diagnostics retained')
 d=json.loads(stdout);(out/('mysql-'+label+'.json')).write_text(json.dumps(d,indent=2)+'\n');print(label,d);return d
def run(mode):return collect(subprocess.Popen(args+[mode],cwd=repo,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True),mode)
run('inventory');run('init')
for mode in ['tts','sale']:
 ps=[subprocess.Popen(args+[mode,str(i)],cwd=repo,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True)for i in range(2)];deadline=time.time()+20
 while not all((root/(mode+'-ready-'+str(i))).exists()for i in range(2)):
  if time.time()>deadline:raise RuntimeError('Readiness barrier timeout')
  time.sleep(.02)
 (root/(mode+'-go')).touch()
 for i,p in enumerate(ps):collect(p,mode+'-'+str(i))
run('proof');run('delete')

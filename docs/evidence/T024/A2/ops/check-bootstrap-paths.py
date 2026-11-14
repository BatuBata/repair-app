#!/usr/bin/env python3
"""Execute full bootstrap control flow with disposable host-command stubs."""
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

OPS=Path(__file__).resolve().parent
source=(OPS/'bootstrap.sh').read_text()
results=[]
STUB='''#!/usr/bin/env python3
import json,os,pathlib,shutil,stat,sys
root=pathlib.Path(os.environ['MOCK_ROOT']); name=pathlib.Path(sys.argv[0]).name; args=sys.argv[1:]
with open(root/'calls','a') as f:f.write(json.dumps([name,*args])+'\\n')
if name=='stat':
 s=os.stat(args[-1]);print(0 if args[1]=='%u' else oct(stat.S_IMODE(s.st_mode))[2:])
elif name in ('id','getent','systemctl','php-fpm8.3'):pass
elif name=='nginx':
 if args==['-T']:print('existing host valid config')
 else:
  countfile=root/'nginx-count'; count=int(countfile.read_text())+1 if countfile.exists() else 1;countfile.write_text(str(count))
  if count==int(os.environ.get('FAIL_NGINX_TEST','0')):sys.exit(17)
elif name=='apt-get':
 if args[0]=='-s':print('Inst php8.3-cli (8.3.36 Sury)\\n0 upgraded, 12 newly installed, 0 to remove and 172 not upgraded.')
 else:
  if os.environ.get('FAIL_APT'):sys.exit(19)
  (root/'etc/php/8.3/fpm/pool.d').mkdir(parents=True)
elif name=='update-alternatives':
 file=root/'alternatives.json';state=json.loads(file.read_text());group=args[1]
 if args[0]=='--query':print('Status: '+state[group]['status']+'\\nValue: '+state[group]['value'])
 elif args[0]=='--set':
  state[group]={'status':'manual','value':args[2]};file.write_text(json.dumps(state))
 else:sys.exit(98)
elif name=='install':
 directory=False;mode=0o755;pos=[];i=0
 while i<len(args):
  if args[i]=='-d':directory=True;i+=1
  elif args[i] in ('-o','-g','-m'):
   if args[i]=='-m':mode=int(args[i+1],8)
   i+=2
  else:pos.append(args[i]);i+=1
 if directory:
  for value in pos:pathlib.Path(value).mkdir(parents=True,exist_ok=True);pathlib.Path(value).chmod(mode)
 else:shutil.copyfile(pos[0],pos[1]);pathlib.Path(pos[1]).chmod(mode)
elif name=='certbot':
 if '--run-deploy-hooks' in args:sys.exit(2)
 if args[0]=='certonly':
  cert=root/'etc/letsencrypt/live/demo-repair-app.scannara.asia';cert.mkdir(parents=True)
  for file in ('fullchain.pem','privkey.pem'):(cert/file).write_text('synthetic-only')
 elif args[0]!='renew':sys.exit(98)
else:sys.exit(98)
'''
with tempfile.TemporaryDirectory(prefix='repair-T024-paths-') as temp:
 for case,nginx_failure,apt_failure in [('success',0,False),('initial-http-validation-fails',1,False),('tls-validation-fails',2,False),('apt-install-fails',0,True)]:
  root=Path(temp).resolve()/case;root.mkdir();bin_dir=root/'bin';bin_dir.mkdir()
  for name in ('stat','id','getent','systemctl','php-fpm8.3','nginx','apt-get','update-alternatives','install','certbot'):
   path=bin_dir/name;path.write_text(STUB);path.chmod(0o700)
  bundle=root/'root/repair-app-t024-bootstrap';bundle.mkdir(parents=True);bundle.chmod(0o700)
  for name in ('php-fpm.conf','nginx-http.conf','nginx-https.conf','logrotate.conf','renewal-hook.sh'):
   shutil.copyfile(OPS/name,bundle/name)
   if name=='renewal-hook.sh':
    text=(bundle/name).read_text().replace('/usr/sbin/nginx',str(bin_dir/'nginx')).replace('/bin/systemctl',str(bin_dir/'systemctl'))
    (bundle/name).write_text(text)
   (bundle/name).chmod(0o600)
  for name in ('etc/nginx/sites-available','etc/nginx/sites-enabled','etc/logrotate.d','etc/letsencrypt/renewal-hooks/deploy'):(root/name).mkdir(parents=True)
  (root/'etc/os-release').write_text('ID=debian\nVERSION_ID=12\n')
  initial={}
  for group in ('php','phar','phar.phar'):
   runtime=bin_dir/f'{group}8.2';runtime.write_text('#!/bin/sh\nexit 0\n');runtime.chmod(0o700)
   initial[group]={'status':'auto','value':str(runtime)}
  (root/'alternatives.json').write_text(json.dumps(initial));(root/'calls').write_text('')
  adapted=source.replace('$EUID','0')
  def rewrite(match):
   key=match[0]
   if key in ('/usr/sbin/nginx','/usr/sbin/php-fpm8.3','/bin/systemctl'):return str(bin_dir/Path(key).name)
   return str(root/key.lstrip('/'))
  adapted=re.sub(r'/usr/sbin/nginx|/usr/sbin/php-fpm8\.3|/bin/systemctl|/root|/etc|/var',rewrite,adapted)
  script=bundle/'bootstrap.sh';script.write_text(adapted);script.chmod(0o600)
  env=dict(os.environ,PATH=f'{bin_dir}:{os.environ["PATH"]}',MOCK_ROOT=str(root),FAIL_NGINX_TEST=str(nginx_failure))
  if apt_failure:env['FAIL_APT']='1'
  run=subprocess.run(['bash',str(script),'PREPARE:demo-repair-app'],env=env,capture_output=True,text=True)
  assert (run.returncode==0)==(case=='success'),(case,run.returncode,run.stdout,run.stderr)
  state=json.loads((root/'alternatives.json').read_text())
  assert all(state[group]=={'status':'manual','value':initial[group]['value']} for group in initial),(case,state)
  enabled=root/'etc/nginx/sites-enabled/demo-repair-app';available=root/'etc/nginx/sites-available/demo-repair-app'
  if case=='success':
   assert enabled.is_symlink() and available.read_text()==(bundle/'nginx-https.conf').read_text()
   assert (root/'var/www/demo-repair-app/.deploy-pending').is_file()
   calls=[json.loads(x) for x in (root/'calls').read_text().splitlines()]
   renewal=['certbot','renew','--cert-name','demo-repair-app.scannara.asia','--dry-run']
   assert any(x==renewal for x in calls)
   reloads=[i for i,x in enumerate(calls) if x==['systemctl','reload','nginx']]
   assert len(reloads)==3 and calls.index(renewal)<reloads[-1]
   assert (root/'nginx-count').read_text()=='3'
  elif case=='initial-http-validation-fails':
   assert not enabled.is_symlink() and available.read_text()==(bundle/'nginx-http.conf').read_text()
   assert 'new enabled link removed' in run.stderr
  elif case=='tls-validation-fails':
   assert enabled.is_symlink() and available.read_text()==(bundle/'nginx-http.conf').read_text()
   assert 'restored validated HTTP503' in run.stderr
  else:
   assert not enabled.exists() and not available.exists()
  results.append({'case':case,'expected':'success only for success case; preserve all runtime values/manual mode; scoped Nginx recovery; no go-live','actual':'matched','status':'PASS'})
(OPS/'bootstrap-path-checks.json').write_text(json.dumps({'status':'PASS','cases':results,'limits':'Commands, root ownership and paths mocked; no real host/package/ACME changes.'},indent=2)+'\n')
print(f'PASS {len(results)} bootstrap path cases (disposable stubs only)')

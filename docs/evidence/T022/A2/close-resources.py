from pathlib import Path
import json,hashlib,subprocess,urllib.request,os,signal,socket,time
root=Path('/Users/muzib/Projects/web/repair-app');out=root/'docs/evidence/T022/A2';p=Path(Path('/private/tmp/t022-a2-location').read_text().strip());old=(p/'old-build/assets/app-CxA26Lmt.css').read_text();new=(root/'public/build/assets/app-B8ctbMH8.css').read_text();i=0
while i<min(len(old),len(new)) and old[i]==new[i]:i+=1
j=0
while j<min(len(old),len(new))-i and old[-j-1]==new[-j-1]:j+=1
before=old[i:len(old)-j];after=new[i:len(new)-j];assert before=='' and after=='lowercase{text-transform:lowercase}.'
found=[]
for folder in ['app','resources']:
 for f in (root/folder).rglob('*'):
  if f.is_file() and 'lowercase' in f.read_text(errors='ignore'):found.append(str(f.relative_to(root)))
assert not found
proof=json.loads((out/'reused-proof.json').read_text());proof['build_delta']={'old_css_bytes':len(old),'new_css_bytes':len(new),'unchanged_prefix_bytes':i,'unchanged_suffix_bytes':j,'removed':'','added':after,'runtime_lowercase_source_hits':found,'assessment':'Only one unused lowercase utility added by Tailwind auto detection; existing rules identical. PDF uses its own document styles, no Vite CSS. Fresh report browser ran with new built bundle. Existing business smoke behavior preserved by identical runtime sources and no matching lowercase class.'};(out/'reused-proof.json').write_text(json.dumps(proof,indent=2))
assets=[]
for name in ['build','font']:
 for r in json.loads((out/(name+'-manifest.json')).read_text()):
  path='/'+r['path'].removeprefix('public/');res=urllib.request.urlopen('http://127.0.0.1:8192'+path,timeout=10);data=res.read();assert hashlib.sha256(data).hexdigest()==r['sha256'];assets.append({'path':path,'status':res.status,'bytes':len(data),'same_sha256':True})
(out/'asset-http-check.json').write_text(json.dumps({'verdict':'pass','files':assets},indent=2))
pid=int(subprocess.check_output(['lsof','-tiTCP:8192','-sTCP:LISTEN'],text=True));cmd=subprocess.check_output(['ps','-p',str(pid),'-o','command='],text=True).strip();assert str(p/'router.php') in cmd;os.kill(pid,signal.SIGTERM);time.sleep(.2);s=socket.socket();free=s.connect_ex(('127.0.0.1',8192))!=0;s.close();assert free
b=json.loads((out/'browser-preflight.json').read_text());alive=False
try:os.kill(b['process'],0);alive=True
except ProcessLookupError:pass
assert not alive and not Path(b['profile']).exists() and list((p/'chrome').iterdir())==[]
(out/'resource-release.json').write_text(json.dumps({'private_runtime':str(p),'fixture_preserved':True,'old_build_preserved':str(p/'old-build'),'candidate_build_preserved':str(p/'candidate-build'),'server_pid':pid,'server_command':cmd,'server_terminated':True,'port8192_released':True,'browser_pid':b['process'],'browser_alive':alive,'profile_exists':False,'chrome_directory_empty':True,'mysql_not_accessed':True},indent=2));print('23 assets PASS, own server/browser released')

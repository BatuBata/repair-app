from pathlib import Path
import hashlib,json,subprocess,urllib.request,shutil
root=Path('/Users/muzib/Projects/web/repair-app');out=root/'docs/evidence/T022/A1'
row=lambda f:{'path':str(f.relative_to(root)),'bytes':f.stat().st_size,'sha256':hashlib.sha256(f.read_bytes()).hexdigest()}
source=[]
for folder in ['app','bootstrap','config','database','resources','routes','tests','deploy']:
 for f in sorted((root/folder).rglob('*')):
  if f.is_file() and not str(f.relative_to(root)).startswith(('bootstrap/cache/','database/database.sqlite')):source.append(row(f))
for name in ['composer.json','composer.lock','package.json','package-lock.json','phpunit.xml','phpstan.neon','vite.config.js','docs/PANDUAN.md','docs/DEPLOYMENT.md']:
 f=root/name
 if f.is_file():source.append(row(f))
(out/'source-manifest.json').write_text(json.dumps(source,indent=2))
build=[row(f) for f in sorted((root/'public/build').rglob('*')) if f.is_file()];fonts=[row(f) for f in sorted((root/'public/fonts').rglob('*')) if f.is_file()]
(out/'build-manifest.json').write_text(json.dumps(build,indent=2));(out/'font-manifest.json').write_text(json.dumps(fonts,indent=2));shutil.copy('/private/tmp/t022-input-equality.json',out/'input-source-equality.json')
checks=[]
for r in build+fonts:
 path='/'+r['path'].removeprefix('public/');res=urllib.request.urlopen('http://127.0.0.1:8192'+path,timeout=10);data=res.read();h=hashlib.sha256(data).hexdigest();assert h==r['sha256'],path
 checks.append({'url_path':path,'status':res.status,'bytes':len(data),'sha256_matches_disk':True})
(out/'asset-http-check.json').write_text(json.dumps({'verdict':'pass','files':checks,'build_warnings':'Optional fontaine absent; IBM Plex absolute font URLs unresolved at build time but all local font assets return exact200 bytes at runtime; no dependency added'},indent=2))
(out/'runtime-versions.txt').write_text('\n'.join(subprocess.check_output(cmd,cwd=root,text=True,stderr=subprocess.STDOUT).strip() for cmd in [['/opt/homebrew/opt/php@8.3/bin/php','-v'],['node','--version'],['npm','--version'],['git','--version']]))
print(json.dumps({'sourceFiles':len(source),'buildFiles':len(build),'fontFiles':len(fonts),'httpFiles':len(checks),'sourceDigest':hashlib.sha256((out/'source-manifest.json').read_bytes()).hexdigest()},indent=2))

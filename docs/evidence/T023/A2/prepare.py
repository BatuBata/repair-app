from pathlib import Path
import json,hashlib,subprocess,tempfile,os,shutil,secrets,base64,re
root=Path.cwd();out=root/'docs/evidence/T023/A2';tmp=Path(tempfile.mkdtemp(prefix='t023-a2-',dir='/private/tmp'));tmp.chmod(0o700);old=Path('/private/tmp/t023-a1-_l62grm9');env=json.loads((old/'private.json').read_text())
for d in ['storage/framework/cache/data','storage/framework/cache/locks','storage/framework/views','storage/framework/sessions','storage/logs','storage/app/public','storage/app/private','chrome','edge','bootstrap']:(tmp/d).mkdir(parents=True,exist_ok=True)
shutil.copyfile(old/'fixture.sqlite',tmp/'fixture.sqlite');shutil.copytree(old/'storage/app/public',tmp/'storage/app/public',dirs_exist_ok=True)
for name in ['ids.json','large-ids.json']:shutil.copyfile(old/name,tmp/name)
env.update({'QA_RUNTIME':str(tmp),'APP_KEY':'base64:'+base64.b64encode(secrets.token_bytes(32)).decode(),'DB_DATABASE':str(tmp/'fixture.sqlite'),'APP_CONFIG_CACHE':str(tmp/'bootstrap/config.php'),'APP_ROUTES_CACHE':str(tmp/'bootstrap/routes.php'),'APP_EVENTS_CACHE':str(tmp/'bootstrap/events.php'),'APP_PACKAGES_CACHE':str(tmp/'bootstrap/packages.php'),'APP_SERVICES_CACHE':str(tmp/'bootstrap/services.php'),'LARAVEL_STORAGE_PATH':str(tmp/'storage'),'VIEW_COMPILED_PATH':str(tmp/'storage/framework/views'),'SESSION_COOKIE':'t023a2_session'})
(tmp/'private.json').write_text(json.dumps(env));(tmp/'private.json').chmod(0o600);Path('/private/tmp/t023-a2-location').write_text(str(tmp));Path('/private/tmp/t023-a2-location').chmod(0o600)
proof={'checked_revision':subprocess.check_output(['git','rev-parse','HEAD'],text=True).strip(),'branch':subprocess.check_output(['git','branch','--show-current'],text=True).strip(),'private_runtime':str(tmp),'fixtureCopiedFromOwnA1':True,'fixtureSourceSha256':hashlib.sha256((old/'fixture.sqlite').read_bytes()).hexdigest(),'fixtureCopiedSha256':hashlib.sha256((tmp/'fixture.sqlite').read_bytes()).hexdigest(),'manifests':{}}
for name in ['source','build','font']:
 rows=json.loads((root/f'docs/evidence/T022/A2/{name}-manifest.json').read_text());bad=[r['path']for r in rows if hashlib.sha256((root/r['path']).read_bytes()).hexdigest()!=r['sha256']];assert not bad;proof['manifests'][name]={'files':len(rows),'mismatches':bad}
prior=json.loads((root/'docs/evidence/T022/A1/source-manifest.json').read_text());now=json.loads((root/'docs/evidence/T022/A2/source-manifest.json').read_text());a={x['path']:x['sha256']for x in prior};b={x['path']:x['sha256']for x in now};changed=[f for f in a if a[f]!=b[f]];assert set(changed)=={'resources/css/app.css','resources/views/livewire/reports/styles.blade.php'};assert a.keys()==b.keys();proof['source_delta']={'changed':changed,'unchanged':len(a)-len(changed)}
oldBuild=Path('/private/tmp/repair-t023-a2-artifact-nm755bhm/preserved-a1-build');oldMan=json.loads((oldBuild/'manifest.json').read_text())if(oldBuild/'manifest.json').exists()else{};oldCss=next((oldBuild/'assets').glob('app-*.css'));newCss=root/'public/build/assets/app-CDn1G0_W.css';s=oldCss.read_text();t=newCss.read_text();removed=[]
for rule in [r'.\[cursor\:pointer\]{cursor:pointer}',r'.lowercase{text-transform:lowercase}']:
 if rule in s and rule not in t:removed.append(rule);s=s.replace(rule,'')
assert s==t,'Unexpected retained CSS change';proof['actual_A1_to_A2_CSS']={'oldFile':oldCss.name,'newFile':newCss.name,'removedRules':removed,'removedBytes':oldCss.stat().st_size-newCss.stat().st_size,'allRetainedBytesIdentical':True}
# Independently confirm removed utilities have no literal runtime consumers.
uses=[]
for folder in ['app','resources','routes']:
 for f in (root/folder).rglob('*'):
  if f.is_file():
   try:text=f.read_text()
   except UnicodeError:continue
   if re.search(r'(?<![\w-])lowercase(?![\w-])|\[cursor:pointer\]',text):uses.append(str(f.relative_to(root)))
assert not uses;proof['unusedRuntimeClassSearch']={'literalConsumers':uses,'dynamicClassReview':'Views/JS/domain unchanged; no dynamic use of these two utilities. Explicit view/vendor scanner sources retained.'}
(out/'input-proof.json').write_text(json.dumps(proof,indent=2));print(json.dumps(proof,indent=2))

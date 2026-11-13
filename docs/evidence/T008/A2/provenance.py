import pathlib,subprocess,json,hashlib
repo=pathlib.Path.cwd(); out=repo/'docs/evidence/T008/A2'; old=pathlib.Path('/Users/muzib/orca/workspaces/repair-app/repair-t005-settings'); pin='db5de005a345de644a7aa9056ea93f5a81117a3d'; prior='f6b23c974b319520b80991d67200c05ddd5562f4'
def git(*a): return subprocess.check_output(['git','-c','core.quotepath=false',*a],text=True).strip()
def sha(p): return hashlib.sha256(p.read_bytes()).hexdigest()
paths=git('ls-files','app','bootstrap','config','database','routes','resources','public','tests','composer.json','composer.lock','package.json','package-lock.json','vite.config.js').splitlines()
assert not git('diff',pin,'--',*paths)
assert not git('diff',pin,'HEAD','--',*paths)
changed=git('diff','--name-only',prior,pin,'--',*paths).splitlines()
rest=json.loads((repo/'docs/evidence/T007/A2/build-restoration.json').read_text())['expected']
assetproof={p:{'expected':v,'current':sha(repo/p),'old':sha(old/p)} for p,v in rest.items()}
assert all(v['expected']==v['current']==v['old'] for v in assetproof.values())
req=json.loads((old/'docs/evidence/T008/requirements-assets-hashes.json').read_text())
req={p:v for p,v in req.items() if not p.startswith('canonical/')}
reqdiff=[p for p,v in req.items() if sha(repo/p)!=v]
assert not reqdiff
unchanged=[p for p in paths if p not in changed and (old/p).exists() and sha(repo/p)==sha(old/p)]
result={'checked_revision':pin,'head':git('rev-parse','HEAD'),'source_identical_to_pin':True,'changed_since_A1':changed,'unchanged_vs_A1_files':len(unchanged),'unchanged_vs_A1_sha256':{p:sha(repo/p) for p in unchanged},'asset_hashes':assetproof,'requirements_and_mockup_unchanged':len(req),'requirements_differences':reqdiff,'env_sha256_private':False,'source_snapshot':{p:sha(repo/p) for p in paths},'root_env_sha256':sha(repo/'.env')}
# Keep the root env hash private; public evidence only records equality after checks.
private=pathlib.Path('/private/tmp/t008-a2-location').read_text().strip(); (pathlib.Path(private)/'env-sha256').write_text(result.pop('root_env_sha256'))
(out/'provenance-before.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps({'source_identical_to_pin':True,'changed_since_A1':changed,'assets_match':len(assetproof),'requirements_match':len(req)}))

import collections, datetime, hashlib, json, pathlib, re
from zoneinfo import ZoneInfo
root=pathlib.Path('/var/www/demo-repair-app/current')
backup=pathlib.Path('/home/muzib/deploy-demo-repair-app/saas-s010-20261117T041815Z/backup')
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
manifest=backup/'SHA256SUMS'
entries=[]
for line in manifest.read_text().splitlines():
    expected, name=line.split(None,1); p=(backup/name.removeprefix('*')).resolve()
    assert p.is_relative_to(backup.resolve())
    assert sha(p)==expected
    entries.append({'match':True,'bytes':p.stat().st_size})
assert len(entries)==12
expected_env=(backup/'environment.before.sha256').read_text().split()[0]
assert sha(root/'.env')==expected_env
uploads=root/'storage/app/public'
expected_uploads={}
for line in (backup/'uploads.files.sha256').read_text().splitlines():
    digest,name=line.split(None,1);expected_uploads[name.removeprefix('./')]=digest
actual_uploads={str(p.relative_to(uploads)):sha(p) for p in uploads.rglob('*') if p.is_file()}
assert expected_uploads==actual_uploads
revision=(root/'.release-revision').read_text().strip()
assert revision=='99f64bf59ff1489825333aa821015a281db611ea'
markers={str(p):p.exists() for p in [root/'.deploy-pending',root.parent/'.deploy-pending',root/'storage/framework/down']}
assert not any(markers.values())
since=datetime.datetime(2026,11,17,4,37,24,tzinfo=datetime.timezone.utc)
laravel=[]
for p in (root/'storage/logs').glob('*.log'):
    recent=0;levels=collections.Counter()
    for line in p.read_text(errors='replace').splitlines():
        match=re.match(r'\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\].*?\.(ERROR|CRITICAL|ALERT|EMERGENCY)',line)
        if match and datetime.datetime.strptime(match[1],'%Y-%m-%d %H:%M:%S').replace(tzinfo=ZoneInfo('Asia/Makassar'))>=since:recent+=1;levels[match[2]]+=1
    laravel.append({'recent_error_entries':recent,'levels':dict(levels)})
nginx={'readable':False}
p=pathlib.Path('/var/log/nginx/error.log')
try:
    counts=collections.Counter();total=0;target=0
    for line in p.read_text(errors='replace').splitlines():
        m=re.match(r'(\d{4}/\d\d/\d\d \d\d:\d\d:\d\d) \[([a-z]+)\]',line)
        if not m:continue
        dt=datetime.datetime.strptime(m[1],'%Y/%m/%d %H:%M:%S').replace(tzinfo=ZoneInfo('Asia/Jakarta'))
        if dt<since:continue
        total+=1
        if 'demo-repair-app.scannara.asia' in line or '/var/www/demo-repair-app' in line:target+=1;counts[m[2]]+=1
    nginx={'readable':True,'since_deployment_all_hosts_entries':total,'target_entries':target,'target_levels':dict(counts),'source_timezone':'Asia/Jakarta'}
except PermissionError:pass
print(json.dumps({'verdict':'pass' if nginx['readable'] and nginx['target_entries']==0 and all(x['recent_error_entries']==0 for x in laravel) else 'review','observed_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'revision':revision,'marker_exists':markers,'backup_manifest_sha256':sha(manifest),'backup_entries_verified':len(entries),'environment_identical':True,'uploaded_files_identical':True,'uploaded_files_count':len(actual_uploads),'laravel_log_files':len(laravel),'laravel':laravel,'nginx':nginx,'log_since_utc':since.isoformat(),'raw_log_contents_saved':False},indent=2))

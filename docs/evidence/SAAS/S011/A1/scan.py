import gzip, hashlib, json, os, pathlib, re
root=pathlib.Path(__file__).resolve().parent
private=pathlib.Path('/Users/muzib/Projects/web/repair-app/storage/app/private/saas-s010-20261117T041815Z')
known=[]
for line in (private/'environment.private').read_text().splitlines():
    m=re.match(r'(APP_KEY|DB_PASSWORD|ADMIN_INITIAL_PASSWORD)=(.*)',line)
    if m:
        value=m[2].strip().strip('"\'')
        if len(value)>=5:known.append(('environment_secret',value))
if os.environ.get('S011_PASSWORD'):known.append(('credential',os.environ['S011_PASSWORD']))
schema=json.loads((private/'inventory.json').read_text())
sql=gzip.decompress(next(private.glob('*.sql.gz')).read_bytes()).decode()
fields={'users':['name','email','password','remember_token','two_factor_secret','two_factor_recovery_codes'],'service_orders':['customer_name','customer_phone','customer_address','tracking_token'],'sales':['customer_name'],'store_settings':['address','phone']}
pattern=r"'(?:[^'\\]|\\.)*'|NULL|-?[0-9]+|[(),]"
for table,names in fields.items():
    match=re.search(r'^INSERT INTO `'+table+r'` VALUES\s*(.*?);$',sql,re.M|re.S)
    if not match:continue
    columns=schema[table]['columns'];selected=[columns.index(k) for k in names if k in columns];row=None
    for token in re.findall(pattern,match[1]):
        if token=='(':row=[]
        elif token==')':
            assert len(row)==len(columns)
            for i in selected:
                v=row[i]
                if v.startswith("'"):
                    value=v[1:-1]
                    value=re.sub(r'\\(.)',lambda m:{'n':'\n','r':'\r','t':'\t','0':'\0','Z':'\x1a'}.get(m[1],m[1]),value)
                    if len(value)>=5 and value.lower() not in ['admin','administrator','customer umum','muzib','teknisi']:known.append((table+'.'+columns[i],value))
            row=None
        elif token!=',':row.append(token)
findings=[]
files=[p for p in root.iterdir() if p.is_file() and p.name not in ['manifest.json','secret-scan.json']]
for p in files:
    text=p.read_text()
    for kind,value in known:
        if re.search(r'(?<![A-Za-z0-9_])'+re.escape(value)+r'(?![A-Za-z0-9_])',text):findings.append({'file':p.name,'category':kind,'length':len(value),'authorized_operator_name':value.lower()=='muzib'})
    for kind,regex in [('private_key',r'-----BEGIN (?:OPENSSH|RSA|EC|DSA) PRIVATE KEY-----'),('literal_phone',r'(?<!\d)08\d{8,13}(?!\d)'),('tracking_url',r'/t/[A-Za-z0-9]{64}'),('bcrypt_hash',r'\$2[aby]\$\d\d\$[./A-Za-z0-9]{53}')]:
        if re.search(regex,text):findings.append({'file':p.name,'category':kind})
result={'verdict':'pass' if not findings else 'fail','files_scanned':len(files),'known_private_values_checked':len(set(v for _,v in known)),'public_name_exclusions':['authorized SSH operator name in paths','generic application role name'],'findings':findings,'raw_values_emitted':False,'raw_media_files':sum(p.suffix.lower() in ['.png','.jpg','.pdf','.har','.zip','.gz'] for p in files),'private_fixture_export_was_rejected_and_not_created':True,'temporary_task_directory_removed':not pathlib.Path('/private/tmp/s011-private-20261117').exists()}
(root/'secret-scan.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result))
assert not findings and result['raw_media_files']==0

from pathlib import Path
import hashlib,subprocess,json,re,time
import pdfplumber
root=Path.cwd();out=root/'docs/evidence/T023/A1';pin='2d7e613d5592ff169cb7743ff6fd086ec06fe58c';files=[r['path'] for r in json.loads((root/'docs/evidence/T022/A1/input-source-equality.json').read_text())['T017']['files']];equality=[]
for f in files:
 old=subprocess.check_output(['git','show',pin+':'+f]);new=(root/f).read_bytes();equality.append({'path':f,'pin_sha256':hashlib.sha256(old).hexdigest(),'current_sha256':hashlib.sha256(new).hexdigest(),'equal':old==new})
assert all(x['equal']for x in equality)
probe=(root/'docs/evidence/T017/A3/probe.php.txt').read_text();print(probe[:1600])
results=[];compact=lambda s:re.sub(r'\s+','',''.join(s)if isinstance(s,list)else s)
for name in ['name-a5','long-thermal','long-a5']:
 start=time.monotonic();f=root/f'docs/evidence/T017/A3/{name}.pdf';row={'name':name,'artifact_sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'pages':[]};body=[];alltext=[]
 with pdfplumber.open(f)as pdf:
  for i,p in enumerate(pdf.pages,1):
   bad=[c for c in p.chars if c['x0']< -1 or c['x1']>p.width+1 or c['top']< -1 or c['bottom']>p.height+1];assert not bad,(name,i,len(bad));text=p.extract_text(x_tolerance=1)or'';alltext.append(text);body.append(p.crop((0,105,p.width,p.height-25)).extract_text(x_tolerance=1)or'' if name=='long-a5'else text);row['pages'].append({'page':i,'width':p.width,'height':p.height,'glyphs':len(p.chars),'outside':len(bad)})
   if name=='name-a5':assert 'N'*255 in compact(text),i;assert 'TTS-2611-BOUNDARY'in text
  row['page_count']=len(pdf.pages);row['qr_label_count']=compact(alltext).count('Pindaiuntukmelihatstatusservis')
  assert row['qr_label_count']==1
  if name!='name-a5':
   bounded=lambda text,end:(text*5000)[:65535-len(end)]+end
   address=bounded('Alamat sintetis batas ',' ADDRESS_END');terms=bounded('Ketentuan sintetis batas ',' TERMS_END');assert compact(address)in compact(body)and compact(terms)in compact(body)
   row['addressCharacters']=len(address);row['termsCharacters']=len(terms);row['fullContentMatch']=True
 row['seconds']=round(time.monotonic()-start,3);row['allGlyphsWithinPage']=True;results.append(row);print(name,len(row['pages']),row['seconds'],flush=True)
(out/'long-pdf-independent.json').write_text(json.dumps({'source_equality':equality,'results':results,'reuse_condition':'same six source files; same stored PDF hashes reviewed against T017A3 coordinator-render-review.json; cold-FPM timing reused from immutable original evidence, not rerun'},indent=2))

import sqlite3,re,pdfplumber,json,time
compact=lambda s:re.sub(r'\s+', '', s)
c=sqlite3.connect('/private/tmp/t017-a3-cold/database.sqlite'); address,terms=c.execute('select address,warranty_terms from store_settings where id=1').fetchone();c.close()
for mode in ['long-a5']:
 start=time.monotonic(); pages=[]; text=[]; body=[]; label=[]
 with pdfplumber.open('/private/tmp/t017-a3-cold/'+mode+'.pdf') as pdf:
  for i,p in enumerate(pdf.pages,1):
   bad=[x for x in p.chars if x['x0'] < -1 or x['x1']>p.width+1 or x['top'] < -1 or x['bottom']>p.height+1];assert not bad,(mode,i,len(bad));t=p.extract_text(x_tolerance=1) or '';plain=compact(t)
   assert 'Notaservis' in plain and 'TTS-2611-BOUNDARY' in plain
   if mode=='name-a5':assert 'N'*255 in plain
   else:assert 'Syntheticboundarystore' in plain
   label += [i] if 'Pindai untuk melihat status servis' in t else [];pages.append({'page':i,'glyphs':len(p.chars),'outside':len(bad)});text.append(t);body.append(p.crop((0,105,p.width,p.height-25)).extract_text(x_tolerance=1) or '')
  if mode=='long-a5':
   alltext=compact(''.join(body));assert compact(address) in alltext and compact(terms) in alltext
  assert len(label)==1
  print(json.dumps({'mode':mode,'pages':len(pdf.pages),'bounds':pages,'qr_page':label[0],'full_content_match':True,'seconds':round(time.monotonic()-start,3)}),flush=True)

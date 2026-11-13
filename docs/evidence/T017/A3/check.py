import sqlite3,re,pdfplumber,json,time
start=time.monotonic();db=sqlite3.connect('/private/tmp/t017-a3-fpm-final/database.sqlite'); name,address,terms=db.execute('select name,address,warranty_terms from store_settings where id=1').fetchone();db.close()
with pdfplumber.open('/private/tmp/t017-a3-fpm-final/long-thermal.pdf') as pdf:
 text=[]; bounds=[]; labels=[]
 for i,p in enumerate(pdf.pages,1):
  cs=p.chars; bad=[c for c in cs if c['x0'] < -1 or c['x1'] > p.width+1 or c['top'] < -1 or c['bottom'] > p.height+1];bounds.append({'page':i,'chars':len(cs),'outside':len(bad),'width_pt':p.width,'height_pt':p.height});text.append(p.extract_text(x_tolerance=1) or ''); labels+= [{'page':i,**x} for x in p.search('Pindai untuk melihat status servis')];print('page',i,'glyphs',len(cs),'outside',len(bad),flush=True)
  assert not bad
 compact=lambda s:re.sub(r'\s+', '', s)
 full=compact(''.join(text));assert compact(address) in full;assert compact(terms) in full;assert 'ADDRESS_END' in full and 'TERMS_END' in full;assert len(labels)==1
 print(json.dumps({'address_chars':len(address),'terms_chars':len(terms),'full_content_match':True,'markers':True,'bounds':bounds,'qr_label':labels,'seconds':round(time.monotonic()-start,3)}),flush=True)

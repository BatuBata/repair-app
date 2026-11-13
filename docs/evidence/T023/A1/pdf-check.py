from pathlib import Path
import pdfplumber,json,re,subprocess,hashlib
from pypdf import PdfReader
p=Path('docs/evidence/T023/A1');rows=[];poppler='/Users/muzib/.cache/codex-runtimes/codex-primary-runtime/dependencies/bin/override/pdftoppm'
for f in sorted(p.glob('*.pdf')):
 if f.stem in ['name-a5']:continue
 with pdfplumber.open(f)as doc:
  text='\n'.join(page.extract_text(x_tolerance=1)or''for page in doc.pages);assert text;pages=[]
  for page in doc.pages:
   bad=[c for c in page.chars if c['x0']< -1 or c['x1']>page.width+1 or c['top']< -1 or c['bottom']>page.height+1];assert not bad,(f.name,len(bad));a5='a5'in f.stem;assert abs(page.width-(419.53 if a5 else 164.409))<0.1,(f.name,page.width);pages.append({'width_pt':page.width,'height_pt':page.height,'outsideGlyphs':len(bad)})
  assert '700.000'not in text and '1.200.000'not in text,(f.name,'cost leak')
  if f.stem.startswith('invoice')or f.stem=='public-a5':assert '1.100.000'in text or'1.125.000'in text
  if f.stem=='public-a5':assert '6281234567890'not in text and 'Customer invoice'not in text
  if 'logo'in f.stem:assert 'Anugerah QA'in text and'Jalan Contoh 23'in text and'0411-123456'in text and'Garansi berlaku'in text
  (p/(f.stem+'-text.txt')).write_text(text)
 reader=PdfReader(f);font=[];images=[]
 for page in reader.pages:
  for k,ref in page['/Resources'].get('/Font',{}).items():
   item=ref.get_object();desc=item.get('/FontDescriptor');desc=desc or(item['/DescendantFonts'][0].get_object().get('/FontDescriptor')if item.get('/DescendantFonts')else None);desc=desc.get_object()if desc else None;font.append({'name':str(item.get('/BaseFont')),'embedded':bool(desc and any(k in desc for k in ['/FontFile','/FontFile2','/FontFile3']))})
  images.extend([{'width':i.image.width,'height':i.image.height}for i in page.images])
 assert all(x['embedded']for x in font),(f.name,font)
 if'logo'in f.stem:assert any(i['width']==120 and i['height']==40 for i in images)
 rows.append({'file':f.name,'sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'pages':pages,'fonts':font,'images':images,'pass':True})
 if f.stem in ['tts-thermal','invoice-a5','public-a5','invoice-logo-a5','invoice-logo-thermal','sale-a5']:
  subprocess.run([poppler,'-f','1','-l','1','-r','140','-png','-singlefile',str(f),str(p/(f.stem+'-independent'))],check=True,capture_output=True)
(p/'pdf-independent.json').write_text(json.dumps(rows,indent=2));print(json.dumps({'files':len(rows),'allSizesGlyphBoundsEmbeddedFontsPrivacyPass':True}))

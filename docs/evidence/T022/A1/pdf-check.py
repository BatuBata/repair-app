from pathlib import Path
from pypdf import PdfReader
import json,subprocess
out=Path('/Users/muzib/Projects/web/repair-app/docs/evidence/T022/A1');rows=[]
for file in sorted(out.glob('*.pdf')):
 r=PdfReader(file);text='\n'.join(p.extract_text() or '' for p in r.pages);sizes=[{'width':round(float(p.mediabox.width),3),'height':round(float(p.mediabox.height),3)} for p in r.pages]
 assert text.strip(),file
 assert '634.567' not in text and '634567' not in text,file
 if 'a5' in file.name:assert all(abs(s['width']-419.528)<1 and abs(s['height']-595.276)<1 for s in sizes),sizes
 else:assert all(abs(s['width']-164.409)<1 for s in sizes),sizes
 if 'sale' not in file.name:assert '1.000.000' in text,file
 if 'public' in file.name:assert '6281234567890' not in text and 'Customer smoke T022' not in text
 rows.append({'file':file.name,'pages':len(r.pages),'sizes_points':sizes,'costAbsent':True,'pricePresent':True,'publicIdentityAbsent':'public' in file.name})
 (out/(file.stem+'-text.txt')).write_text(text)
for name in ['admin-a5','admin-thermal','public-a5']:
 f=out/(name+'.pdf')
 if f.exists():subprocess.run(['/Users/muzib/.cache/codex-runtimes/codex-primary-runtime/dependencies/bin/override/pdftoppm','-f','1','-singlefile','-scale-to','1000','-png',str(f),str(out/(name+'-render'))],check=True,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
(out/'pdf-check.json').write_text(json.dumps({'verdict':'pass','tool':'pypdf + Poppler render','documents':rows,'limits':'Small smoke samples; extreme pagination/QR and all visual matrix belong T023; no physical printer checked'},indent=2));print(json.dumps(rows,indent=2))

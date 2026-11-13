from pathlib import Path
from PIL import Image,ImageDraw
import json
p=Path('docs/evidence/T023/A1');files=json.loads((p/'screen-matrix.json').read_text())['cases'];out=p/'reviews';out.mkdir(exist_ok=True)
for width in [360,768,1280]:
 rows=[x for x in files if x['width']==width]
 cw=360 if width==360 else 480;ch=640;cols=3
 for batch in range(0,len(rows),6):
  canvas=Image.new('RGB',(cw*cols,ch*2),'#dce1e4');d=ImageDraw.Draw(canvas)
  for i,r in enumerate(rows[batch:batch+6]):
   im=Image.open(p/r['screenshot']).convert('RGB');im.thumbnail((cw-12,ch-30));x=i%cols*cw+(cw-im.width)//2;y=i//cols*ch+25;canvas.paste(im,(x,y));d.text((i%cols*cw+6,i//cols*ch+5),r['name']+' '+str(width),fill='black')
  canvas.save(out/f'all-{width}-{batch//6+1}.jpg')
# Same-screen reference pairs at normal phone composition, top fold + complete source PNGs retained.
map={'login':'login','dashboard':'dashboard','tts-list':'tts','tts-new':'tts-baru','tts-received':'tts-detail','invoice-form':'tts-rincian','tts-success':'tts-sukses','tracking-invoice':'tracking','tts-receipt':'struk','reports':'laporan'}
for name,ref in map.items():
 canvas=Image.new('RGB',(1080,945),'#dce1e4');d=ImageDraw.Draw(canvas)
 for i,(label,f)in enumerate([('APP '+name,p/f'screens/{name}-360.png'),('MOCKUP '+ref,p/f'references/{ref}-360.png')]):
  im=Image.open(f).convert('RGB').crop((0,0,360,900));canvas.paste(im,(i*540+90,35));d.text((i*540+90,10),label,fill='black')
 canvas.save(out/f'compare-{name}.jpg')

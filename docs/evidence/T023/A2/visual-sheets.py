from pathlib import Path
from PIL import Image,ImageDraw
p=Path('docs/evidence/T023/A2')
for width in [360,768,1280]:
 canvas=Image.new('RGB',(1440,1700),'#dce1e4');d=ImageDraw.Draw(canvas)
 for i,mode in enumerate(['sample','large-negative','large-positive']):
  im=Image.open(p/f'reports-{mode}-{width}.png').convert('RGB')
  for j,crop in enumerate([im.crop((0,0,width,min(900,im.height))),im.crop((0,max(0,im.height-1100),width,im.height))]):
   crop.thumbnail((470,800));canvas.paste(crop,(i*480+(480-crop.width)//2,j*850+30));d.text((i*480+8,j*850+8),f'{mode} {width} '+('top'if j==0 else'table/lower'),fill='black')
 canvas.save(p/f'visual-reports-{width}.jpg')
 canvas=Image.new('RGB',(1440,1000),'#dce1e4');d=ImageDraw.Draw(canvas)
 for i,(label,f)in enumerate([('APP SAMPLE',p/f'reports-sample-{width}.png'),('APPROVED MOCKUP',Path(f'docs/evidence/T023/A1/references/laporan-{width}.png'))]):
  im=Image.open(f).convert('RGB').crop((0,0,width,1024 if width==768 else 900));im.thumbnail((700,940));canvas.paste(im,(i*720+(720-im.width)//2,35));d.text((i*720+12,10),label+f' {width}',fill='black')
 canvas.save(p/f'compare-approved-{width}.jpg')
print('six visual comparison sheets ready')

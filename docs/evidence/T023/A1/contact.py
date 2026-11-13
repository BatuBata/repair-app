from pathlib import Path
from PIL import Image,ImageOps,ImageDraw
p=Path('docs/evidence/T023/A1');files=sorted((p/'screens').glob('*-360.png'))
for batch in range(0,len(files),6):
 selected=files[batch:batch+6];canvas=Image.new('RGB',(1080,1450),'#dce1e4');d=ImageDraw.Draw(canvas)
 for i,f in enumerate(selected):
  im=Image.open(f).convert('RGB');im.thumbnail((350,680));x=(i%3)*360+(360-im.width)//2;y=(i//3)*725+25;canvas.paste(im,(x,y));d.text(((i%3)*360+8,(i//3)*725+5),f.stem,fill='black')
 canvas.save(p/f'contact-360-{batch//6+1}.jpg')

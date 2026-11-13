from pathlib import Path
import subprocess,hashlib,json
root=Path.cwd();out=root/'docs/evidence/T023/A1';groups=[('T002-numbering','4a0667c',['app/Services/DocumentNumber.php','app/Services/CreateServiceOrder.php','app/Models/ServiceOrder.php','app/Models/Sale.php','database/migrations']),('T016-mysql-domain','545b52a275687703e6046270afb45e01a0334922',['app/Actions/Sales/CreateSale.php','app/Actions/Sales/DeleteSale.php','app/Services/CreateServiceOrder.php','app/Services/DocumentNumber.php','app/Http/Controllers/TrackingController.php','app/Models/ServiceOrder.php','app/Models/Product.php','app/Models/Sale.php','app/Models/User.php']),('T020-operational','6095a5ca34a5b28bf65a481007d69b21a8bc7084',['deploy'])];all=[]
for name,pin,paths in groups:
 files=[]
 for path in paths:
  p=root/path
  files+=sorted(str(f.relative_to(root))for f in p.rglob('*')if f.is_file())if p.is_dir()else[path]
 rows=[]
 for f in files:
  before=subprocess.check_output(['git','show',pin+':'+f]);after=(root/f).read_bytes();rows.append({'path':f,'pin_sha256':hashlib.sha256(before).hexdigest(),'current_sha256':hashlib.sha256(after).hexdigest(),'equal':before==after})
 all.append({'cases':name,'source_pin':subprocess.check_output(['git','rev-parse',pin],text=True).strip(),'files':rows,'all_equal':not any(not r['equal']for r in rows)})
(out/'reuse-proof.json').write_text(json.dumps(all,indent=2));print(json.dumps([{'cases':x['cases'],'equal':x['all_equal'],'changed':[r['path']for r in x['files']if not r['equal']]}for x in all],indent=2))

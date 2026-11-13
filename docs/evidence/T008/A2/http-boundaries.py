import pathlib,json,http.cookiejar,urllib.request,urllib.parse,urllib.error,re,subprocess,secrets,time
repo=pathlib.Path.cwd();out=repo/'docs/evidence/T008/A2';root=pathlib.Path(pathlib.Path('/private/tmp/t008-a2-location').read_text().strip()); secret=json.loads((root/'private.json').read_text());base='http://127.0.0.1:8188';results=[]
def fixture(*args):
 p=subprocess.run(['python3',str(out/'runner.py'),'fixture',*args],capture_output=True,text=True);assert p.returncode==0,p.stdout
class Session:
 def __init__(self): self.client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.token=None
 def request(self,url,data=None,headers=None):
  try:
   r=self.client.open(urllib.request.Request(url,data,headers or {}));return r.status,r.read().decode(),urllib.parse.urlparse(r.url).path
  except urllib.error.HTTPError as e:return e.code,e.read().decode(),urllib.parse.urlparse(e.url).path
 def login(self,user='admin'):
  status,page,path=self.request(base+'/login'); assert status==200
  self.token=re.search(r'name="_token" value="([^"]+)"',page)[1]
  status,page,path=self.request(base+'/login',urllib.parse.urlencode({'_token':self.token,'username':'t008_'+user,'password':secret['QA_PASSWORD']}).encode())
  assert (status,path)==((404,'/tts/baru') if user=='teknisi' else (200,'/'))
  if status==200:self.token=re.search(r'name="_token" value="([^"]+)"',page)[1]
  return path
 def upload(self,url):
  boundary='QA'+secrets.token_hex(8);png=(repo/'public/icons/ticket-192.png').read_bytes()
  body=(f'--{boundary}\r\nContent-Disposition: form-data; name="files[]"; filename="independent.png"\r\nContent-Type: image/png\r\n\r\n').encode()+png+f'\r\n--{boundary}--\r\n'.encode()
  return self.request(url,body,{'Content-Type':'multipart/form-data; boundary='+boundary,'Accept':'application/json','X-CSRF-TOKEN':self.token})
def grant():
 fixture('grant');return json.loads((root/'grant.json').read_text())['url']
def files(): return {str(p.relative_to(root/'storage/app')) for p in (root/'storage/app').rglob('*') if p.is_file()}
def observe(id,actual,expected):
 assert actual==expected,(id,actual,expected)
 results.append({'id':id,'expected':expected,'actual':actual,'verdict':'pass'})
fixture('transition','restore');s=Session();s.login();url=grant();before=files();status,data,path=s.upload(url)
observe('UPLOAD-ACTIVE-ADMIN-REAL-HTTP',{'status':status,'returned_paths':len(json.loads(data).get('paths',[])),'new_file_count':len(files()-before)},{'status':200,'returned_paths':1,'new_file_count':2})
before=files();bad=url[:-1]+('0' if url[-1]!='0' else '1');status,_,_=s.upload(bad);observe('UPLOAD-TAMPERED-SIGNATURE',{'status':status,'new_files':len(files()-before)},{'status':401,'new_files':0})
for state in ['demote','deactivate']:
 fixture('transition','restore');s=Session();s.login();url=grant();fixture('transition',state);before=files();status,_,_=s.upload(url)
 observe('UPLOAD-STALE-GRANT-'+state.upper(),{'status':status,'new_files':len(files()-before)},{'status':403,'new_files':0})
 if state=='demote':
  status,_,path=s.request(base+'/mulai');observe('LAUNCHER-CURRENT-ROLE-AFTER-USERS-DEMOTION',{'status':status,'path':path},{'status':404,'path':'/tts/baru'})
  for p in ['/','/dashboard','/pengaturan/pengguna','/pengaturan/toko','/pengaturan/kategori']:
   status,_,_=s.request(base+p,headers={'Accept':'application/json'});observe('DEMOTED-ADMIN-ROUTE-'+p,{'status':status},{'status':403})
 else:
  status,_,_=s.request(base+'/mulai',headers={'Accept':'application/json'});observe('INACTIVE-SESSION-INVALIDATED',{'status':status},{'status':401})
fixture('transition','restore');url=grant();before=files();guest=Session();status,page,_=guest.request(base+'/login');guest.token=re.search(r'name="_token" value="([^"]+)"',page)[1];status,_,_=guest.upload(url)
observe('UPLOAD-GUEST-EXISTING-GRANT',{'status':status,'new_files':len(files()-before)},{'status':401,'new_files':0})
tech=Session();tech.login('teknisi');# TTS target404 has no CSRF; fetch permitted profile.
status,page,_=tech.request(base+'/profil');tech.token=re.search(r'name="_token" value="([^"]+)"',page)[1];before=files();status,_,_=tech.upload(url)
observe('UPLOAD-TECHNICIAN-EXISTING-GRANT',{'status':status,'new_files':len(files()-before)},{'status':403,'new_files':0})
fixture('transition','restore')
result={'checked_revision':'db5de005a345de644a7aa9056ea93f5a81117a3d','transport':'actual localhost HTTP with real encrypted session cookies, CSRF and multipart PNG','grant':'actual Store::_startUpload, URL private','transitions':'actual Users::edit/save via second synthetic admin','temporary_file_delta':'includes byte file and Livewire metadata; no public/logo save performed','observations':results,'verdict':'pass','credentials_logged':False}
(out/'http-boundaries.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps({'verdict':'pass','observations':len(results)}))

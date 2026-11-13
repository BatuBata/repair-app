import socket,struct,time,json
sock=socket.socket(socket.AF_UNIX);sock.settimeout(40);sock.connect('/private/tmp/t017-a3-fpm-final/fpm.sock')
def record(kind,data=b''):
 pad=(-len(data))%8;return struct.pack('!BBHHBB',1,kind,1,len(data),pad,0)+data+b'\0'*pad
def length(n):return bytes([n]) if n<128 else struct.pack('!I',n|0x80000000)
def param(k,v):
 k=k.encode();v=v.encode();return length(len(k))+length(len(v))+k+v
params={'SCRIPT_FILENAME':'/private/tmp/t017-a3-fpm-final/probe.php','SCRIPT_NAME':'/probe.php','REQUEST_METHOD':'GET','SERVER_PROTOCOL':'HTTP/1.1','GATEWAY_INTERFACE':'CGI/1.1','SERVER_NAME':'localhost','SERVER_PORT':'80','REMOTE_ADDR':'127.0.0.1','CONTENT_LENGTH':'0','QUERY_STRING':''}
start=time.monotonic();sock.sendall(record(1,struct.pack('!HB5x',1,0))+record(4,b''.join(param(k,v) for k,v in params.items()))+record(4)+record(5));out=b'';err=b'';status=None
def read(n):
 b=b''
 while len(b)<n:
  x=sock.recv(n-len(b))
  if not x:raise RuntimeError('FastCGI socket closed')
  b+=x
 return b
while True:
 ver,kind,rid,size,pad,_=struct.unpack('!BBHHBB',read(8));body=read(size);read(pad)
 if kind==6:out+=body
 if kind==7:err+=body
 if kind==3:status=struct.unpack('!IB3x',body);break
sock.close();assert status==(0,0),status
head,body=out.split(b'\r\n\r\n',1); report=json.loads(body);assert report['mode']=='long-thermal' and report['max_execution_time']=='30' and report['memory_limit']=='128M' and report['private_database'];assert not err,err.decode()
print(json.dumps({'transport':'private PHP-FPM FastCGI','fpm_status':status,'request_wall_seconds':round(time.monotonic()-start,3),'report':report}))

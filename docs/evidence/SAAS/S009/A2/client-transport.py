#!/usr/bin/env python3
import json,os,pathlib,subprocess,sys
r=pathlib.Path(__file__).resolve().parent.parent
name=pathlib.Path(sys.argv[0]).name
with (r/'calls.jsonl').open('a') as f:f.write(json.dumps([name,*sys.argv[1:]])+'\n')
if sys.argv[1:2] and sys.argv[1].startswith('--defaults-file='):
 cnf=sys.argv[1].split('=',1)[1]
 subprocess.run(['docker','--context','colima','cp',cnf,'saas-s009-a2-qa:'+cnf],check=True,capture_output=True)
binary='mariadb-dump' if name=='mysqldump' else 'mariadb'
os.execvp('docker',['docker','--context','colima','exec','-i','saas-s009-a2-qa',binary,*sys.argv[1:]])

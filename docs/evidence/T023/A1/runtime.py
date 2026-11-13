from pathlib import Path
import subprocess,json,os
root=Path.cwd();out=root/'docs/evidence/T023/A1';tmp=Path('/private/tmp/t023-a1-location').read_text().strip();env={**os.environ,**json.loads(Path(tmp+'/private.json').read_text())};php='/opt/homebrew/opt/php@8.3/bin/php'
for mode,cmd in [('config',[php,str(out/'fixture.php.txt'),'config']),('migrate',[php,'artisan','migrate','--force']),('seed',[php,str(out/'fixture.php.txt'),'seed'])]:
 r=subprocess.run(cmd,cwd=root,env=env,capture_output=True,text=True);(out/('runtime-'+mode+'.log')).write_text(r.stdout+r.stderr);print(mode,r.returncode);assert r.returncode==0
# Bind only owned localhost port; config cache pins paths for web bootstrap.
s=subprocess.Popen([php,'-S','127.0.0.1:8193','-t','public','vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],cwd=root,env=env,stdout=open(tmp+'/server.log','w'),stderr=subprocess.STDOUT,start_new_session=True);Path(tmp+'/server.pid').write_text(str(s.pid));(out/'server-preflight.json').write_text(json.dumps({'pid':s.pid,'port':8193,'privateRuntime':tmp,'database':tmp+'/fixture.sqlite','config':env['APP_CONFIG_CACHE'],'storage':env['LARAVEL_STORAGE_PATH'],'session':'private files','cache':'private files','views':env['VIEW_COMPILED_PATH'],'fontCacheInitiallyEmpty':not any(Path(tmp+'/fonts').iterdir()),'noSharedEnvMutation':True},indent=2));print('server',s.pid)

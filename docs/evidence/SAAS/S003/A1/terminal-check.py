import os, pty, select, sqlite3, subprocess, tempfile, time, secrets, pathlib
root='/Users/muzib/Projects/web/repair-app'
php='/opt/homebrew/opt/php@8.3/bin/php'
with tempfile.TemporaryDirectory(prefix='s003-pty-') as temp:
    database=str(pathlib.Path(temp)/'test.sqlite')
    pathlib.Path(database).touch()
    env={**os.environ, 'APP_ENV':'testing','DB_CONNECTION':'sqlite','DB_DATABASE':database,'DB_URL':'','CACHE_STORE':'array','SESSION_DRIVER':'array','LOG_CHANNEL':'stderr'}
    migration=subprocess.run([php,'artisan','migrate','--force','--no-interaction'],cwd=root,env=env,capture_output=True)
    assert migration.returncode==0, 'isolated migration failed'
    primary,secondary=pty.openpty()
    process=subprocess.Popen([php,'artisan','tenant:create'],cwd=root,env=env,stdin=secondary,stdout=secondary,stderr=secondary)
    os.close(secondary)
    password=secrets.token_urlsafe(20)
    prompts=[(b'Nama toko',b'Toko Terminal\n'),(b'Nama owner',b'Owner Terminal\n'),(b'Username owner',b'owner.terminal\n'),(b'Password awal',password.encode()+b'\n')]
    transcript=b''
    step=0
    deadline=time.monotonic()+20
    while time.monotonic()<deadline:
        ready,_,_=select.select([primary],[],[],0.1)
        if ready:
            try: chunk=os.read(primary,65536)
            except OSError: break
            if not chunk: break
            transcript+=chunk
            if step<len(prompts) and prompts[step][0] in transcript:
                # Secret question disables terminal echo after rendering its prompt.
                time.sleep(0.1)
                os.write(primary,prompts[step][1]);step+=1
        if process.poll() is not None and not ready: break
    if process.poll() is None:
        process.kill()
        raise AssertionError('interactive process timed out')
    os.close(primary)
    assert process.returncode==0, 'interactive provisioning failed'
    assert password.encode() not in transcript, 'password echoed'
    db=sqlite3.connect(database)
    tenant=db.execute("select id from tenants where name='Toko Terminal'").fetchone()[0]
    assert db.execute('select role, tenant_id from users where username=?',('owner.terminal',)).fetchone()==('admin',tenant)
    assert db.execute('select count(*) from store_settings where tenant_id=?',(tenant,)).fetchone()[0]==1
    assert db.execute('select count(*) from categories where tenant_id=?',(tenant,)).fetchone()[0]==0
    db.close()
    print('PASS: terminal interaktif nyata; exit=0; password tidak terlihat pada transcript; tenant/owner/store atomik dan tanpa preset.')

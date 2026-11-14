from pathlib import Path
import sqlite3,json,sys
root=Path(sys.argv[1]);assert str(root).startswith('/private/tmp/t023-a2-')
ids=json.loads((root/'large-ids.json').read_text());c=sqlite3.connect(root/'fixture.sqlite')
assert ids=={'services':[8,9],'sales':[3,4]}
for id in ids['services']:c.execute('update service_order_items set price=4294967295,cost=0 where service_order_id=?',(id,))
for id in ids['sales']:c.execute('update sale_items set price=4294967295,cost=0 where sale_id=?',(id,))
c.commit();print('Only copied own A1 large fixture rows switched positive; no new transaction or source change.')

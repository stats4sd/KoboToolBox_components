from mysql.connector import MySQLConnection, Error
from pathlib import Path
from dotenv import load_dotenv

import sys
import os
import errno
import csv

base_path = Path(__file__).parent.parent
env_path = base_path / ".env"
load_dotenv(dotenv_path=env_path)

user = os.getenv('DB_USERNAME')
passwd = os.getenv('DB_PASSWORD')
host = os.getenv('DB_HOST')
db = os.getenv('DB_DATABASE')
table = sys.argv[1]  # table or view you want to save
filename = sys.argv[2]  # the name of the file to create


filename = "%s.csv" % filename
# hard coded to match the "media" driver in config/filesystems.php
csv_path = base_path / "storage" / "app" / "public" / "media" / filename

## check the folders exist
if not os.path.exists(os.path.dirname(csv_path)):
    try:
        os.makedirs(os.path.dirname(csv_path))
    except OSError as exc: # guard against race condition
        if exc.errno != errno.EEXIST:
            raise

## delete old version of the file
if os.path.exists(csv_path):
    os.remove(csv_path)



# setup db connection
try:
    con = MySQLConnection(user=user, passwd=passwd, host=host, db=db)
    cursor = con.cursor()

    query = "SELECT * FROM %s;" % table
    cursor.execute(query)


    with open(csv_path, 'w+', newline='') as csv_file:
        column_names = [i[0] for i in cursor.description]
        writer = csv.writer(csv_file, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        writer.writerow(column_names)

        for row in cursor.fetchall():
            writer.writerow(row)
    con.commit()

except Error as e:

	print('Error:', e)

finally:

	print("done! Isn't this exciting!")
	cursor.close()
	con.close()

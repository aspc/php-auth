import sys
import psycopg2
import pickle
import json

def user(session_id):
	POSTGRESQL_USERNAME = 'php_admin' # Only has SELECT privs on the user_auth and django_session tables
	POSTGRESQL_PASSWORD = 'oopCQ83th36XrIaT8mtGTO1ErL'
	result = None

	try:
		connection = psycopg2.connect(database='main_django', user=POSTGRESQL_USERNAME, password=POSTGRESQL_PASSWORD)
	except psycopg2.DatabaseError, e:
		result = False

	session_data = _session_data(connection=connection, session_id=session_id)

	if session_data:
		session_data = pickle.loads(session_data[0].decode('base64')[41:]) # The first 40 bytes are a crypto hash that we don't care about
		user_id = session_data['_auth_user_id']

		user_data = _user_data(connection=connection, user_id=user_id)
		if user_data:
			result = json.dumps(user_data)
		else:
			result = json.dumps('no_user')
	else:
		result = json.dumps('no_session')

	connection.close()
	return result

def _session_data(connection, session_id):
	try:
		cursor = connection.cursor()
		cursor.execute("SELECT session_data FROM django_session WHERE session_key='{0}'".format(session_id))
		return cursor.fetchone() # Returns a tuple
	except psycopg2.DatabaseError, e:
		return False
	finally:
		cursor.close()

def _user_data(connection, user_id):
	try:
		cursor = connection.cursor()
		cursor.execute("SELECT username, first_name, last_name, email FROM auth_user WHERE id='{0}'".format(user_id))
		return cursor.fetchone() # Returns a tuple
	except psycopg2.DatabaseError, e:
		return False
	finally:
		cursor.close()

print user(sys.argv[1])
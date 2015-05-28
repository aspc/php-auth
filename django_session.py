import sys
import psycopg2
import pickle
import json

def user(session_id):
	POSTGRESQL_USERNAME = 'php_admin'  # Only has SELECT privs on the user_auth and django_session tables
	POSTGRESQL_PASSWORD = 'oopCQ83th36XrIaT8mtGTO1ErL'

	def _query(query):
		try:
			cursor = connection.cursor()
			cursor.execute(query)
			return cursor.fetchone()  # Returns a tuple
		except psycopg2.DatabaseError, e:
			return _build_error_dict(str(e))
		finally:
			cursor.close()

	def _build_error_dict(message):
		return {'error': message}

	try:
		connection = psycopg2.connect(database='main_django', user=POSTGRESQL_USERNAME, password=POSTGRESQL_PASSWORD)
	except psycopg2.DatabaseError, e:
		return _build_error_dict(str(e))

	session_data = _query("SELECT session_data FROM django_session WHERE session_key='{0}'".format(session_id))

	try:
		if 'error' in session_data:
			return json.dumps(session_data)
		else:
			# Decode the session data and unpickle it
			# The first 40 bytes are a crypto hash that we don't care about
			session_data = pickle.loads(session_data[0].decode('base64')[41:])
			user_id = session_data['_auth_user_id']

			user_data = _query("SELECT username, first_name, last_name, email FROM auth_user WHERE id='{0}'".format(user_id))
			return json.dumps(user_data)
	except Exception, e:
		return _build_error_dict(str(e))
	finally:
		connection.close()

print user(sys.argv[1])
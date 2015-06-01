import sys
import psycopg2
import pickle
import json

def user(session_id):
	db_credentials = {}
	execfile('/srv/www/main/public/inc/config.py', db_credentials)

	def _query(query):
		try:
			cursor = connection.cursor()
			cursor.execute(query)
			return cursor.fetchone()  # Returns a tuple
		except psycopg2.DatabaseError, e:
			return _build_error_dict(message=str(e))
		finally:
			cursor.close()

	def _build_error_dict(message):
		return {'error': message}

	try:
		connection = psycopg2.connect(database='main_django', user=db_credentials['POSTGRESQL_USERNAME'], password=db_credentials['POSTGRESQL_PASSWORD'])
	except psycopg2.DatabaseError, e:
		return _build_error_dict(message=str(e))

	session_data = _query(query="SELECT session_data FROM django_session WHERE session_key='{0}'".format(session_id))

	try:
		if 'error' in session_data:
			return session_data
		else:
			# Decode the session data and unpickle it
			# The first 40 bytes are a crypto hash that we don't care about
			session_data = pickle.loads(session_data[0].decode('base64')[41:])
			user_id = session_data['_auth_user_id']

			user_data = _query(query="SELECT username, first_name, last_name, email FROM auth_user WHERE id='{0}'".format(user_id))
			return user_data
	except Exception, e:
		return _build_error_dict(message=str(e))
	finally:
		connection.close()

print json.dumps(user(sys.argv[1]))
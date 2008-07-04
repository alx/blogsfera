import subprocess
from trac.core import *
from trac.web.api import *
import urlparse
from pprint import pformat

class FireStatsPlugin(Component):
	implements(IRequestFilter)

	def pre_process_request(self, req, handler):
		if not req.path_info.startswith('/chrome'):
			#self.env.log.debug("-req (all):\n%s" % pformat(req.__dict__))
			#self.env.log.debug("-req.environ:\n%s" % pformat(req.environ))
			#self.env.log.debug("-req.environ:\n%s" % pformat(self.reconstruct_url(req)))
			#self.env.log.debug("-req.args:\n%s" % pformat(req.args))

			firestats_location = self.env.config.get('firestats', 'firestats_directory')
			if not firestats_location:
				self.log.warning('FireStats: missing parameter: firestats_directory, aborting')
				return handler

			firestats_site_id = self.env.config.get('firestats', 'firestats_site_id')
			if not firestats_site_id:
				firestats_site_id = '1';

			ip = req.remote_addr	
			referer = req.get_header('referer')
			useragent = req.get_header('user-agent')

			x_forwarded = req.get_header('X-Forwarded-For')
			if not x_forwarded:
				x_forwarded = "";

			url = req.abs_href(req.path_info + (req.environ['QUERY_STRING'] and '?'+req.environ['QUERY_STRING']) or '')
			#self.env.log.debug('test2')
			#url1 = self.reconstruct_url(req)
			#self.env.log.debug("url : abs:  %s , ru : %s\n" % (url, url1))

			if not ip:
				ip = ''
			if not referer:
				referer = ''
			if not useragent:
				useragent = ''
			if not url:
				url = ''
			cmd = '$_SERVER["REMOTE_ADDR"]="'+ip+'";$_SERVER["HTTP_USER_AGENT"]="'+useragent+'";$_SERVER["REQUEST_URI"]="'+url+'";$_SERVER["HTTP_REFERER"]="'+referer+'";$GLOBALS["FS_SITE_ID"]="'+firestats_site_id+'";$GLOBALS["FS_X-Forwarded-For"]="'+x_forwarded+'";include("'+firestats_location+'/php/firestats-hit.php");'
			#self.log.info('executing : ' + cmd)
			subprocess.call(['php','-r',cmd])
		return handler


	def post_process_request(self, req, template, content_type):
		return (template, content_type)

	def reconstruct_url(self, req):
		"""reconstruct the absolute url."""
		host = req.get_header('Host')
		if not host:
			# Missing host header, so reconstruct the host from the
			# server name and port
			default_port = {'http': 80, 'https': 443}
			if req.server_port and req.server_port != default_port[req.scheme]:
				host = '%s:%d' % (req.server_name, req.server_port)
			else:
				host = req.server_name
		return urlparse.urlunparse((req.scheme, host, req.base_path+req.environ['PATH_INFO'], None,req.environ['QUERY_STRING'], None))


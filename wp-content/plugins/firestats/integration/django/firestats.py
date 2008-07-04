from django.utils.cache import patch_vary_headers
from django.utils import translation
from os import system

class FireStatsIntegrationMiddleware:
    def process_request(self, request):
        from django.conf import settings
        # extract firestats php parameters
        firestats_location = settings.FIRESTATS_DIRECTORY
        if not firestats_location:
            self.log.warning('FireStats: missing parameter: firestats_directory, aborting')
            return handler

		firestats_site_id = settings.FIRESTATS_SITE_ID
		if not firestats_site_id:
			firestats_site_id = 0

        (ip, referer, useragent, url) = ('','','','')
        if request.META.has_key('REMOTE_ADDR'):
            ip = request.META['REMOTE_ADDR']	
        if request.META.has_key('HTTP_REFERER'):
            referer = request.META['HTTP_REFERER']
        if request.META.has_key('HTTP_USER_AGENT'):
            useragent = request.META['HTTP_USER_AGENT']
#        x_forwarded = request.get_header('X-Forwarded-For')
#        if x_forwarded and x_forwarded != '':
#            ip = x_forwarded.split(',',1)[0]
        url = request.path
        if not ip:
            ip = ''
        if not referer:
            referer = ''
        if not useragent:
            useragent = ''
        if not url:
            url = ''

        # call firestats php here
        cmd = 'php -r \'$_SERVER["REMOTE_ADDR"]="'+ip+'";$_SERVER["HTTP_USER_AGENT"]="'+useragent+'";$_SERVER["REQUEST_URI"]="'+url+'";$_SERVER["HTTP_REFERER"]="'+referer+'";$GLOBALS["FS_SITE_ID"] = '+site_id+';include("'+firestats_location+'/php/firestats-hit.php");\''
        system(cmd)	
        
#    def process_response(self, request, response):
#        from django.conf import settings
#        return response

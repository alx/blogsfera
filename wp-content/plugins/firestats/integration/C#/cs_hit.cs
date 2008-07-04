public class FireStats
    {
        public static void Send()
        {
            String receiverUrl = "http://" + HttpContext.Current.Request.Url.Host + "/firestats/php/hit.php";

            HttpWebRequest httpWebRequest = (HttpWebRequest)WebRequest.Create(receiverUrl);
            httpWebRequest.Method = "POST";
            httpWebRequest.ContentType = "application/x-www-form-urlencoded";
            httpWebRequest.Credentials = CredentialCache.DefaultCredentials;

            String stringPost = GetStr();
            httpWebRequest.ContentLength = stringPost.Length;
            String response;
            byte[] byteArray = Encoding.UTF8.GetBytes(stringPost);

            Stream dataStream = httpWebRequest.GetRequestStream();
            dataStream.Write(byteArray, 0, byteArray.Length);
            dataStream.Close();

            HttpWebResponse httpWebResponse = (HttpWebResponse)httpWebRequest.GetResponse();
            using (StreamReader streamReader = new StreamReader(httpWebResponse.GetResponseStream()))
            {
                response = streamReader.ReadToEnd();
                streamReader.Close();
            }
        }

        private static String GetStr()
        {
            // your site ID here:
            int SiteID = FireStats.SiteID;

            String IP = HttpContext.Current.Request.UserHostAddress;
            String userAgent = HttpContext.Current.Request.UserAgent;
            String url = HttpContext.Current.Request.Url.AbsoluteUri;
            Uri urlReferrerUri = HttpContext.Current.Request.UrlReferrer;

            String referrer = null;
            if (urlReferrerUri != null)
            {
                if (!urlReferrerUri.IsLoopback)
                    referrer = urlReferrerUri.AbsoluteUri;
            }

            String siteId = SiteID.ToString();

            String returnStr = "IP=" + IP + "&USERAGENT=" + userAgent + "&URL=" + url + "&SITE_ID=" + siteId;
            returnStr += (referrer != null) ? "&REF=" + referrer : "";

            returnStr = returnStr.Replace(" ", "%20").Replace("(", "%28").Replace(")", "%29").Replace("/", "%2F");
            return returnStr;
        }

        public static int SiteID
        {
            get
            {
                int defaultValue = 1;
                int returnValue = (System.Configuration.ConfigurationManager.AppSettings["fs_site_id"] != null) ? int.Parse(System.Configuration.ConfigurationManager.AppSettings["fs_site_id"]) : defaultValue;
                return returnValue;
            }
        }
    }
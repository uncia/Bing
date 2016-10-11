<?php
//exit("!");
header("contetn-type:text/html;charset=utf-8");
function getIpInfo($ip) {
	$return = "";
    $a = @file_get_contents("http://ip.chinaz.com/?ip=". $ip);
	preg_match_all("/<span class=\"Whwtdhalf w50-0\">(.*)<\/span>/",$a,$arr);
	if(!empty($arr[1])) {
		$return = $arr[1][1];
	}
    return $return;
}
function getIp($url) {
	$return = array();
    $url = str_replace(array("http://","https://","/"),"",$url);
    $ip = gethostbyname($url);
	$ipInfo = getIpInfo($ip);
	$return = array(
		'ip'=> $ip,
		'info'=>$ipInfo
	);
    return $return ;
}

function getChinaz($ip) {
	$return = array();
    $a = @file_get_contents("http://s.tool.chinaz.com/same?s=". $ip);
    @preg_match_all("/<a href='(.*?)' target=_blank>/",$a,$arr);
	foreach($arr[1] as $k=>$v) {
		$return[] = array(
                'title' => '',
                'domain' => $v,
            );
	}
    return $return;
}
function get114best($ip) {
    $return = array();
    $url="http://www.114best.com/ip/114.aspx?w=".$ip;
    $res = @file_get_contents($url);
    preg_match_all("/<img alt=\"(.*?)\" src=\"view.gif\"/",$res,$domainarr);
    preg_match_all("/src=\"view.gif\" \/><br>(.*?)<br>/s",$res,$titlearr);
    if(!empty($domainarr[1])) {
        foreach($domainarr[1] as $k=>$v) {
             $return[] = array(
                'title' => trim($titlearr[1][$k]),
                'domain' => 'http://'.$v,
            );
        }
    }
    return $return;
}
function getBingApi($ip) {
	$return = array();
	$url="https://api.datamarket.azure.com/Bing/Search/v1/Web?\$format=json&Query=";

	$key = 'eJaFxP6clCKFrjes9UX+PD9f8nD1QLaGCUBMUKa8CwI';
	$url=$url.urlencode('\'ip:'.$ip.'\'');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, ''.":".$key);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	$jsonval=json_decode($output,true);
	$domainInfo=$jsonval['d']['results'];
	$domainarr = array();
	$titlearr = array();
	foreach($domainInfo as $key=>$val) {
		$urlInfo = parse_url($val['Url']);
		if(isset($urlInfo['port'])) {
			$port = ':'.$urlInfo['port'];
		}  else {
			$port = '/';
		}
		$url = $urlInfo['scheme'].'://'.$urlInfo['host'].$port;
		$domainarr[] = $url;
		$titlearr[] = $val['Title'];
	}
	$domainarr = array_unique($domainarr);
	foreach ($titlearr as $key => $value) {
		if(!isset($domainarr[$key])) {
			unset($titlearr[$key]);
		}
	}
	foreach ($titlearr as $k => $v) {
		$return[] = array(
			'title' => $v,
			'domain' => $domainarr[$k],
		);
	}
	return $return;
}
function Export($data) {
    header("Content-Type:application/force-download");
    header("Content-Disposition:attachment;filename=phpinfo.me.txt");
    echo $data;
}

function getBing($ip) {
    $return = array();
    $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => 30,
                //'proxy' => 'tcp://115.47.46.152:1080',
                'request_fulluri' => True,
                'header'=> "User-Agent: BaiduSpider\r\nAccept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3",
            )
        )
    );
    $first = 1;
    $res = array();
    $n=0;
    while(true) {
        $url = "http://cn.bing.com/search?q=ip:{$ip}&go=%E6%8F%90%E4%BA%A4&qs=n&first={$first}&form=QBRE&pq=ip:{$ip}&sc=0-0&sp=-1&sk=&cvid=5e52385772e24683a0bdf047de60abfc";
        $first = $first + 10;
        $result = file_get_contents($url, False, $ctx); 
        preg_match_all('/<h2><a target="_blank" href="((http|https):\/\/([\w|\.]+)\/)([\w|\/|&|=|:|\.|\?]+)?" h="ID=\w+,\w+\.\w+">(.*?)<\/a><\/h2>/',$result,$arr);
    
        if(!empty($arr[1])) {
            for($i=0;$i<count($arr[1]);$i++) {
                $res[] = array(
                    'domain' => $arr[1][$i],
                    'title' => $arr[5][$i],
                );
            }
        
        } 
        //print_r($res);
    
        if(!preg_match('/<div class="sw_next">/', $result)) {
            break;
        }

    }
    if(!empty($arr[1])) {
        foreach($res as $k=>$v) {
            $titlearr[] = $v['title'];
            $domainarr[] = $v['domain'];
        }
        
        $domainarr = array_unique($domainarr);
        foreach ($titlearr as $key => $value) {
            if(!isset($domainarr[$key])) {
                unset($titlearr[$key]);
            }
        }
        foreach ($titlearr as $k => $v) {
            $return[] = array(
                'title' => $v,
                'domain' => $domainarr[$k],
            );
        }
    }
    return $return;
}


function main() {
    if(isset($_REQUEST["action"])) {
		$ref = $_SERVER["HTTP_REFERER"];
		if(!strstr($ref,"bing.php")){
			exit;
		}
        $action = trim($_REQUEST["action"]);
        if($action == "getip") {
            $domain = trim($_REQUEST["domain"]);
            $ip = getIp($domain);
            echo json_encode($ip);
        }
        if($action == "query") {
            $ip = trim($_REQUEST["ip"]);
            $res = getBing($ip);

            echo json_encode($res);
        } elseif ($action == "bingapi") {
            $ip = trim($_REQUEST["ip"]);
            $res = getBingApi($ip);
            echo json_encode($res);
        } elseif ($action == "114best"){
            $ip = trim($_REQUEST["ip"]);
            $res = get114best($ip);
            echo json_encode($res);
		} elseif ($action == "chinaz"){
			$ip = trim($_REQUEST["ip"]);
            $res = getChinaz($ip);
            echo json_encode($res);
        } elseif ($action == "export") {
            $data = $_REQUEST["data"];
            Export($data);
        }
        
    }
}

main();
if(empty($_REQUEST['action'])) {
?>
<!DOCTYPE html>
<html>
    <head>
        <title>在线旁站查询|C段查询|必应接口C段查询 Lcy's Blog - phpinfo.me</title>
        <meta charset="utf-8">
        <meta name="keywords" content="C段查询,旁站查询,必应接口c段查询,c段扫描,Lcy's Blog">
        <meta name="description" content="旁站c段查询 phpinfo.me">
        <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
        <script src="//cdn.bootcss.com/jquery/1.11.3/jquery.min.js"></script>
        <script src="//cdn.bootcss.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
        <style type="text/css" media="screen">
            .main{
                width:90%;
                //border:1px solid red;
                margin-top:20px;
            }
            .ip{
                margin-top:10px;
            }
            dd{
                text-indent:10px;
            }
            .domain{
                
            }
            .title{
                margin-left: 30px;
            
            }
        </style>
          <style>
                .amz-banner{background:url("http://www.sebug.net/static/images/banner_bg1.jpg");background-size:100% 100%;}
                .xoxo{overflow:hidden;height:auto;padding-left:5px}
                .xoxo li{ display:block;float:left;list-style:none;width: 108px;padding:0px;margin:0px;white-space: nowrap;margin-top:10px;text-align:center}
  
                                                                 
        </style>

    </head>
    <body>
        <div class="container">
            <div class="main">
                <h1>在线C段查询 </h1>
                <form class="form-inline" onsubmit="return false">
                    <div class="form-group" style="">
                    <input type="text" id="domain" class="form-control" value="phpinfo.me" placeholder="输入你要查询的ip或域名">
                    </div>
                    <button type="submit" class="btn btn-success" id="getip">获取ip</button>
                    <button type="submit" class="btn btn-warning" id="query_1">查询旁站</button>
                    <button type="submit" class="btn btn-info" id="query">查询C段</button>
                    <button type="submit" class="btn btn-primary" id="getserver">获取web服务器信息</button>
                    <button type="submit" class="btn btn-danger" id="export">导出</button>
                    <select id="api" class="form-control">
                        <option value="bing">必应搜索引擎采集</option>
                        <option value="bingapi">必应api(每月只能查5000次)</option>
                        <option value="114best">114best</option>
						<option value="chinaz">chinaz</option>
                    </select>
                </form>
                <div class="alert alert-info ip" role="alert" style="display:none">IP:<span id="ip"></span><span id="se"></span></div>
                <div class="progress" id="jd" style="display:none">
                  <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="40" id="b" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    <span class="sr-only">40% Complete (success)</span>
                  </div>
                </div>
                
                <dl id="result">

                </dl>
                <form action="" method="post" id="expsub">
                    <input type="hidden" name="action" value="export" />
                    <input type="hidden" name="data" id="data" value="" />
                </form>
                <!-- 多说评论框 start -->
    <div class="ds-thread" data-thread-key="lcyv5" data-title="C段查询" data-url="http://tools.phpinfo.me/bing.php"></div>
<!-- 多说评论框 end -->
<!-- 多说公共JS代码 start (一个网页只需插入一次) -->
<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_5770257'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s6.cnzz.com/stat.php%3Fid%3D5770257' type='text/javascript'%3E%3C/script%3E"));</script>
<script type="text/javascript">
var duoshuoQuery = {short_name:"hacktools"};
    (function() {
        var ds = document.createElement('script');
        ds.type = 'text/javascript';ds.async = true;
        ds.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') + '//static.duoshuo.com/embed.js';
        ds.charset = 'UTF-8';
        (document.getElementsByTagName('head')[0] 
         || document.getElementsByTagName('body')[0]).appendChild(ds);
    })();
    </script>
<!-- 多说公共JS代码 end -->
            </div>

        </div>
        </body>
<script type="text/javascript">
if(window.top !== window.self){ window.top.location = window.location;}  
</script>
    <script type="text/javascript">
        var ipi = 1;
        var isquery = 1;
        $(function() {
            $("#export").click(function() {
                var ex = "";
                $(".domain").each(function() {
                    ex = ex + $(this).html()+"\r\n";
                });
                $("#data").val(ex);
                $("#expsub").get(0).submit();
                
            })
            $("#getip").click(function() {  
                var domain = $("#domain").val();
                if(domain == "") {
                    alert("请输入ip或者域名");
                    return false;
                }
                var $btn = $(this).button('loading');
                $.post("","action=getip&domain="+domain,function(res) {
                    var ip = res.ip;
                    $("#ip").html(ip);
                    $(".ip").show();
                    arr = ip.split(".");
                    start = arr[0] + "." + arr[1] + "." + arr[2] + "." + 1;
                    end = arr[0] + "." + arr[1] + "." + arr[2] + "." + 255;
                    $("#se").html(" 查询ip段：" + start + "-" + end + ' 位置：' +res.info)
                    $btn.button('reset');
                },'json')
            });
            
            $("#query").click(function() {
                ipi=1;
                $("#b").css("width","0%");
                $("#result").html("");
                $("#jd").show();
                query();
                
            });
            $("#query_1").click(function() {
				$("#b").css("width","0%");
                var ip = $("#ip").html();
                if(ip == "") {
                    alert("骚年请先获取Ip哦");
                    return;
                }
                var html = "";
                var api_url = "";
                var api_action=$("#api").val();
                if(api_action == "bing") {
                    api_url = "action=query&ip="+ip
                } else if(api_action == "bingapi") {
                    api_url = "action=bingapi&ip="+ip
                } else if(api_action == "114best") {
                    api_url = "action=114best&ip="+ip
                } else if(api_action == "chinaz") {
                    api_url = "action=chinaz&ip="+ip
                }
                $.post("",api_url,function(res) {
                    $("#jd").show();
                    $("#b").css("width",b+"%");
                    var lengths = 0;
                    if(res) {
                        lengths = res.length;
                    }
                    html += "<dt><span class='serverip'>"+ ip +"</span><span class='serv'></span> <font color='green'>("+ lengths +")</font></dt>";
                    for(var i in res) {
                        html += "<dd><a class='domain' href=\"" + res[i].domain + "\" target=\"_blank\">" + res[i].domain +"</a><span class='title'>"  + res[i].title +"</span></dd>";
                        
                    }
                    $("#result").html(html);
                    $("#b").css("width","100%");
                },"json");
            });
            
            $("#getserver").click(function() {
                $(".serverip").each(function(){
                    var obj = $(this)
                    var serverip = obj.html();
                    $.get("https://phpinfo.me/api/getserver.php?ip="+serverip,function(res) {
                        var html = " - [" + res + "]"
                        obj.parent().find(".serv").html(html);
                    });
                });
            });
        })

        function query() {
            isquery = 1;
            $("#query").click(function() {
                return;
            });

            var html = "";
            var b = (ipi/255) * 100;
            var ip = $("#ip").html();
            if(ip == "") {
                alert("骚年请先获取Ip哦");
                return;
            }
            var arr = ip.split(".");
            var ips = arr[0] + "." + arr[1] + "." + arr[2] + "." + ipi;
            var api_url = "";
                var api_action=$("#api").val();
                if(api_action == "bing") {
                    api_url = "action=query&ip="+ips
                } else if(api_action == "bingapi") {
                    api_url = "action=bingapi&ip="+ips
                } else if (api_action == "114best" || api_action == "chinaz") {
                    alert('该接口无法查询c段！');
					return;
                }
            $.post("",api_url,function(res) {
                $("#b").css("width",b+"%");
                var lengths = 0;
                if(res) {
                    lengths = res.length;
                }
                html += "<dt><span class='serverip'>"+ ips +"</span> <span class='serv'></span><font color='green'>("+ lengths +")</font></dt>";
                for(var i in res) {
                    html += "<dd><a class='domain' href=\"" + res[i].domain + "\" target=\"_blank\">" + res[i].domain +"</a><span class='title'>"+ res[i].title +"</span></dd>";
                    
                }
                $("#result").append(html);
                if(ipi<255) {
                    ipi++;
                    query();
                }
            },"json");
            
        }
    </script>

</html>

<?php
}
?>

<?php
header("contetn-type:text/html;charset=utf-8");
function getIp($url) {
	$data = file_get_contents("http://www.ip138.com/ips138.asp?ip={$url}&action=2");
	preg_match("/(\d+\.\d+\.\d+\.\d+)<\/font>/", $data, $arr);
	if(!empty($arr[1])) {
		return $arr[1];
	}
	return $url;
}

function getChinaz($ip) {
	$a = file_get_contents("http://s.tool.chinaz.com/same?s=". $ip);
	@preg_match_all("/<li><span>(\d+)\.<\/span> <a href='(.*?)'/",$a,$arr);
	return $arr[2];
}

function Export($data) {
	header("Content-Type:application/force-download");
	header("Content-Disposition:attachment;filename=lcy.txt");
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
		preg_match_all('/<h2><a target="_blank" href="((http|https):\/\/([\w|\.]+)\/)([\w|\/|&|=|\.|\?]+)?" h="ID=\w+,\w+\.\w+">(.*?)<\/a><\/h2>/',$result,$arr);
	
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
		$action = trim($_REQUEST["action"]);
		if($action == "getip") {
			$domain = trim($_REQUEST["domain"]);
			$ip = getIp($domain);
			echo $ip;
		}
		if($action == "query") {
			$ip = trim($_REQUEST["ip"]);
			$res = getBing($ip);

			echo json_encode($res);
		} elseif ($action == "chinaz") {
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
		<title>必应接口C段查询|c段查询|旁站查询</title>
		<meta charset="utf-8">
		<meta >
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
				<h1>必应接口C段查询 </h1>
				<form class="form-inline" onsubmit="return false">
					<div class="form-group" style="">
					<input type="text" id="domain" class="form-control" placeholder="输入你要查询的ip或域名">
					</div>
					<button type="submit" class="btn btn-success" id="getip">获取ip</button>
					<button type="submit" class="btn btn-warning" id="query_1">查询旁站</button>
					<button type="submit" class="btn btn-info" id="query">查询C段</button>
					<button type="submit" class="btn btn-danger" id="export">导出</button>
					<select id="api" class="form-control">
						<option value="bing">必应接口</option>
						<option value="chinaz">站长之家接口(不可用)</option>
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
		var ipi = 1;
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
					var ip = res;
					$("#ip").html(ip);
					$(".ip").show();
					arr = ip.split(".");
					start = arr[0] + "." + arr[1] + "." + arr[2] + "." + 1;
					end = arr[0] + "." + arr[1] + "." + arr[2] + "." + 255;
					$("#se").html(" 查询ip段：" + start + "-" + end)
					$btn.button('reset');
				})
			});
			
			$("#query").click(function() {
				ipi=1;
				$("#b").css("width","0%");
				$("#result").html("");
				$("#jd").show();
				query();
				
			});
			$("#query_1").click(function() {
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
					html += "<dt>"+ ip +" <font color='green'>("+ lengths +")</font></dt>";
					for(var i in res) {
						html += "<dd><a class='domain' href=\"" + res[i].domain + "\" target=\"_blank\">" + res[i].domain +"</a><span class='title'>"  + res[i].title +"</span></dd>";
						
					}
					$("#result").html(html);
					$("#b").css("width","100%");
				},"json");
			});
		})

		function query() {
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
				} else if(api_action == "chinaz") {
					api_url = "action=chinaz&ip="+ips
				}
			$.post("",api_url,function(res) {
				$("#b").css("width",b+"%");
				var lengths = 0;
				if(res) {
					lengths = res.length;
				}
				html += "<dt>"+ ips +" <font color='green'>("+ lengths +")</font></dt>";
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

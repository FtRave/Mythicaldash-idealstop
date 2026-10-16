<?php
namespace MythicalDash\Addons\idlestop;
final class MinecraftStatus {
 public static function query(string $host, int $port, int $timeout=3): ?int {
  $e=0; $m="";
  $s=@stream_socket_client("tcp://".$host.":".$port,$e,$m,$timeout);
  if (!$s) return null;
  stream_set_timeout($s,$timeout);
  $vi=function($n) { $o=""; do { $b=$n&127; $n >>= 7; if($n)$b|=128; $o.=chr($b); } while($n); return $o; };
  $rv=function() use ($s) { $v=0; $shift=0; do { $x=fread($s,1); if($x===false||$x==="") return null; $b=ord($x); $v|=($b&127)<<$shift; $shift+=7; if($shift>35)return null; } while($b&128); return $v; };
  $send=function($d) use ($s,$vi) { $p=$vi(strlen($d)).$d; return fwrite($s,$p)===strlen($p); };
  try {
   $h=$vi(0).$vi(767).$vi(strlen($host)).$host.pack("n",$port).$vi(1);
   if(!$send($h)||!$send($vi(0))) return null;
   $len=$rv(); $id=$rv(); $jl=$rv();
   if($len===null||$id!==0||$jl===null||$jl<1||$jl>$len||$jl>1048576)return null;
   $json=""; while(strlen($json)<$jl) { $x=fread($s,$jl-strlen($json)); if($x===false||$x==="")return null; $json.=$x; }
   $d=json_decode($json,true);
   return isset($d["players"]["online"])&&is_numeric($d["players"]["online"])?max(0,(int)$d["players"]["online"]):null;
  } catch(\Throwable $x) { return null; } finally { fclose($s); }
 }
}

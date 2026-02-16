<style>
.qrcode{
		height:60px;
		width:60px;
}
.code {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 2px;
}
.voucher-header {
    align-items: center;
    font-size: 15px;
    font-weight: bold;
    color: #000000;
}
.voucher-footer {
    margin-top: 10px;
    font-size: 10px;
    color: #6c757d;
}
</style>
<table style="display: inline-block;border-collapse: collapse;border: 1px solid #666;margin: 2.5px;width: 182px;overflow:hidden;position:relative;padding: 0px;margin: -5px -5px 2px 2px;border: 1px solid #000000;">
<tbody>
<tr>
<td style="color:#666;" valign="top">
<table style="width:100%;">
<tbody>
<tr>
<td style="width:75px">
<div style="position:relative;z-index:-1;padding: 0px;float:left;">
<div style="position:absolute;top:0;display:inline;margin-top:-100px;width: 0; height: 0; border-top: 230px solid transparent;border-left: 50px solid transparent;border-right:140px solid #DCDCDC; "></div>
</div>
<span class="voucher-header" style="margin:5px 0 0 5px;" width="80" height="15"  alt="logo"> @{{ profile_name }}</span>
</td>	
<td style="width:115px">
<div style="float:right;margin-top:-6px;margin-right:0px;width:5%;text-align:right;font-size:7px;">
</div>
<div class="price" style="text-align:right;font-weight:bold;font-family:Tahoma;font-size:14px;padding-left:17px;color:#e63d00">
@{{ price }}
</div>	
</td>		
</tr>
</tbody>
</table>
</td>
</tr>
<tr>
<td style="color:#666;border-collapse: collapse;" valign="top">
<table style="width:100%;border-collapse: collapse;">
<tbody>
<tr>
<td style="width:95px"valign="top" >
<div style="clear:both;color:#555;margin-top:2px;margin-bottom:2.5px;">
<div style="padding:0px;border-bottom:1px solid#e63d00;text-align:center;font-weight:bold;font-size:8px;">TON CODE D'ACCÈS</div>
<div class="code" style="padding:0px;border-bottom:1px solid#e63d00;text-align:center;font-weight:bold;font-size:14px;;color:#000000;">@{{ code }}</div>
</div>
<div class="voucher-footer" style="clear:both;padding:0 2.5px;font-size:7px;font-weight:bold;color:#000000">
  <div>Données: <strong>@{{ data_limit }}</strong></div>
  <div>Validité: <strong>@{{ validity }}</strong></div>
</div><br><!--
<div style="color:#111;font-size:6px;font-weight:lite;margin-left: -34px;/*margin-right: -42px;*/">
<ol><li style="margin-top: -5px;font-weight:bold">Conserver bien ton ticket,<br> il te sera demandé en cas de reclamation.</li></ol>
</div>-->
</td>	
<td>
<div style="/*float:right;padding:1px;text-align:right;width:25%;margin:0 -1px -10px 0;*/">@{{ qrcode }}</div>
</td>		
</tr>
<tr>
<td style="background:#e63d00;color:#666;padding:0px;" valign="top" colspan="2">
<div style="text-align:left;color:#fff;font-size:9px;font-weight:bold;margin:0px;padding:2.5px;"><i class="icon-base ti tabler-device-mobile"></i>
</svg> &nbsp;<b>@{{ contact }}</b>
</div>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>  
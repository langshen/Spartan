<!DOCTYPE>
<html>
<head>
    <title>数据表名生成器</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">*{ margin:0;padding:0; }body{padding: 24px 48px;}</style>
    <script language="JavaScript" src="https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/js/lib/jquery-1.10.2_d88366fd.js"></script>
</head>

<body>
{eq name="action" value="list"}
    <table>
        <tr>
            <th colspan="7">共{$list.total}个表</th>
        </tr>
        <tr>
            <th>表名称</th>
            <th>表注释</th>
            <th>表行数</th>
            <th>建表时间</th>
            <th>表编码</th>
            <th>引擎</th>
            <th>管理</th>
        </tr>
        {volist name="list.data" id="vo"}
        <tr>
            <td>{$vo.name}</td>
            <td>{$vo.comment}</td>
            <td>{$vo.rows}</td>
            <td>{$vo.create_time}</td>
            <td>{$vo.collation}</td>
            <td>{$vo.engine}</td>
            <td><a href="?action=info&table={$vo.name}">{$vo.status}</a></td>
        </tr>
        {/volist}
    </table>
{/eq}

{eq name="action" value="info"}
    <form id="frmCreate">
        <table>
        <tr>
            <th colspan="7">
                <label>表信息</label>
                <div>
                    <input type="text" value="{$info.name}" name="table_name" />
                    <input type="text" value="{$info.comment}" placeholder="表名：{$info.name}，默认别名：a，表注释自动获取" readonly />
                </div>
                <div>
                    <button type="button" onclick="postCreate(this);">提交生成</button>
                    （文件默认生成在项目根目录Dal文件夹内，请确保有写入权）
                </div>
            </th>
        </tr>
        <tr>
            <th>自动查询</th>
            <th>必填</th>
            <th>函数体</th>
            <th>变量</th>
            <th>提示词</th>
            <th>默认值</th>
            <th>字段名称</th>
            <th>字段注释</th>
            <th>字段类型</th>
            <th>主键</th>
        </tr>
        {volist name="info.fields" id="vo"}
            <tr>
                <td><select name="condition[{$vo.name}]">
                        <option value="0">不可</option>
                        <option value="1" {eq name="vo.condition" value="1"}selected{/eq}>可查</option>
                    </select></td>
                <td><select name="required[{$vo.name}]">
                        <option value="">不设置</option>
                        <option value="null" {eq name="vo.required.0" value="null"}selected{/eq}>可为空</option>
                        <option value="required" {eq name="vo.required.0" value="required"}selected{/eq}>必填</option>
                        <option value="without" {eq name="vo.required.0" value="without"}selected{/eq}>多选填</option>
                    </select></td>
                <td><input name="function[{$vo.name}]" value="{$vo.required.1}" style="width:100px;" /></td>
                <td>
                    <input name="argv1[{$vo.name}]" value="{$vo.required.2.0}" style="width:60px;" />,
                    <input name="argv2[{$vo.name}]" value="{$vo.required.2.1}" style="width:60px;" />
                </td>
                <td>
                    <input name="tip[{$vo.name}]" value="{$vo.required.3}" />
                </td>
                <td><input name="default[{$vo.name}]" value="{$vo.required.4}" style="width:40px;" /></td>
                <td title="{$vo.collation}">{$vo.name} {eq name="vo.pri" value="true"}[主]{/eq}</td>
                <td>{$vo.comment}</td>
                <td>{$vo.type}({$vo.long})</td>
                <td>{$vo.null}({$vo.default})</td>
            </tr>
        {/volist}
    </table>
    </form>
    <script language="JavaScript">
        function postCreate(btn) {
            $.post('?action=save',$('#frmCreate').serialize(),function(json) {
                alert(json[0]);
                if (json[1] === 0){
                    window.location.reload();
                }
            });
        }
    </script>
{/eq}

</body>
</html>

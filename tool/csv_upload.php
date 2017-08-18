<!DOCTYPE html>
<html>
<body>
        <form action="/projects/event_tracking/web/app_dev.php/event/postback/v4" method="post" enctype="multipart/form-data">
            <table>
                <tr>
                    <td>Select CSV file to upload</td>
                    <td>:</td>
                    <td><input type="file" name="csv" id="fileToUpload"></td>
                </tr>
                <tr>
                    <td>Select App Name</td>
                    <td>:</td>
                    <td>
                        <select name="app_name">
                            <option value="com.daidigames.banting">Asian Poker (Android)</option>
                            <option value="id961876128">Asian Poker (IOS)</option>
                            <option value="com.bukalapak.android">Bukalapak (Android)</option>
                            <option value="id1003169137">Bukalapak (IOS)</option>
                            <option value="sg.gumi.bravefrontier">Brave Frontier (Android)</option>
                            <option value="id694609161">Brave Frontier (IOS)</option>
                            <option value="sg.gumi.chainchronicleglobal">Chain Chronicle (Android)</option>
                            <option value="id935189878">Chain Chronicle (IOS)</option>
                            <option value="sg.gumi.wakfu">Wakfu (Android)</option>
                            <option value="id942908715">Wakfu (IOS)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Select Data Source Provider</td>
                    <td>:</td>
                    <td>
                        <select name="provider">
                            <option value="1">AppsFlyer</option>
                            <option value="2">HasOffer</option>
                            <option value="3">Custom</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Select Event Type</td>
                    <td>:</td>
                    <td>
                        <select name="event_type">
                            <option value="install">Install</option>
                            <option value="in-app-event">In App Event</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="right"><input type="submit" value="Upload File" name="submit"></td>
                </tr>
            </table>
</form>
</body>
</html>
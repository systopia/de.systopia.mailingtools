{*-------------------------------------------------------+
| SYSTOPIA MailingTools Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}



<h3>{ts domain='de.systopia.mailingtools'}Adding patterns from {$name} {/ts}</h3>

<table>
    <tr>
        <td><strong>File</strong></td>
        <td><strong>Imported</strong></td>
        <td><strong>Ignored</strong></td>
    </tr>
    {foreach from=$result_counter item=result key=key}
        <td>
            {$key}
        </td>
        <td>
            {$result.inserted}
        </td>
        <td>
            {$result.ignored}
        </td>
    {/foreach}
</table>


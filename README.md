# turkopticon-php-api

This is the read-only PHP API for Turkopticon, an employer review system for Amazon Mechanical Turk.

## Install and configure

1. Copy the files into an appropriate location. The files in /public should be public. The other files should not be.
2. Make sure you have installed the php5-mysqlnd package. This lets you use PHP's mysqli_* functions.
3. Rename dbconn-example.php to dbconn.php.
4. Put your database credentials into dbconn.php.
5. Make sure you have APC enabled.
6. Make the log file:

    ```
    > $ cd /path/to/api/log && touch multi-attrs.php.log
   ```

7. Make sure the log can be written to. For example:

    ```
    > $ cd /path/to/api/log && chmod 777 multi-attrs.php.log
    ```

8. Make sure the log won't grow forever. For example, use logrotate or make a cron job like:

    ```
    > 0 0 1 * * cd /path/to/api/log && tail -n20000 multi-attrs.php.log > multi-attrs.old && echo "" > multi-attrs.log
    ```

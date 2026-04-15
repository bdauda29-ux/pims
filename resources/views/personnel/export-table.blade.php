<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Personnel Export</title>
        <style>
            body { font-family: "Liberation Sans", sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 4px; font-size: 10px; vertical-align: top; }
            th { background: #f3f4f6; font-weight: 700; text-align: left; }
            .meta { margin: 0 0 10px 0; }
            .meta div { font-size: 12px; font-weight: 700; margin: 0 0 2px 0; }
            .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: right; font-size: 10px; font-style: italic; font-family: "Liberation Sans", sans-serif; }
            .content { margin-bottom: 30px; }
        </style>
    </head>
    <body>
        <div class="content">
            @if(!empty($metaLines))
                <div class="meta">
                    @foreach($metaLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif
            <table>
                <thead>
                    <tr>
                        @foreach($headers as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(!empty($generatedOn))
            <div class="footer">Generated on {{ $generatedOn }}</div>
        @endif
    </body>
</html>

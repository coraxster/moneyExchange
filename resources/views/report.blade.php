<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Report</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script src="/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

    <!-- Datetime picker -->
    <script type="text/javascript" src="/js/jquery-3.3.1.slim.min.js"></script>
    <script type="text/javascript" src="/js/moment.min.js"></script>
    <script type="text/javascript" src="/js/tempusdominus-bootstrap-4.min.js"></script>
    <link rel="stylesheet" href="/css/tempusdominus-bootstrap-4.min.css" />

</head>

<body>
<div class="container">

    <h1>Hello report! User: {{ $wallet->user->name }} (WalletId: {{ $wallet->id }})</h1>
    <h2> <span class="badge badge-success">+ {{$depositSum}} {{ $wallet->currency }}</span>
        <span class="badge badge-danger">- {{$withdrawSum}} {{ $wallet->currency }}</span>
    </h2>
    <form method="GET">
        <div class="row">
                <div class="col-sm-3">
                    from
                    <div class="form-group">
                        <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
                            <input type="text" name="from-date" value="{{request('from-date')}}" class="form-control datetimepicker-input" data-target="#datetimepicker1"/>
                            <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
                                <div class="input-group-text">date</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    to
                    <div class="form-group">
                        <div class="input-group date" id="datetimepicker2" data-target-input="nearest">
                            <input type="text" name="to-date" value="{{request('to-date')}}" class="form-control datetimepicker-input" data-target="#datetimepicker2"/>
                            <div class="input-group-append" data-target="#datetimepicker2" data-toggle="datetimepicker">
                                <div class="input-group-text">date</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3" style="margin-top: 24px">
                    <button type="submit" class="btn btn-primary search-btn">Search</button>
                </div>

        </div>
    </form>



    <div class="row">
        <div class="col-sm-12">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">From</th>
                    <th scope="col">Amount</th>
                    <th scope="col">To</th>
                    <th scope="col">Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($ops as $op)
                        @if($wallet === $op->fromWallet)
                            <tr class="table-danger">
                            <td>{{ $op->fromWallet->user->name ?? '-' }}</td>
                            <td> ({{ $op->withdraw }}{{$op->withdraw_money->getCurrency()}}) => </td>
                            <td>{{ $op->toWallet->user->name ?? '-' }}</td>
                        @else
                            <tr class="table-success">
                            <td>{{ $op->toWallet->user->name ?? '-' }}</td>
                            <td> ({{ $op->deposit }} {{$op->deposit_money->getCurrency()}}) => </td>
                            <td>{{ $op->fromWallet->user->name ?? '-' }}</td>
                        @endif
                            <td>{{ $op->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $ops->links() }}
        </div>
    </div>
</div> <!-- /container -->

<script type="text/javascript">
    $(function () {
        $('#datetimepicker1').datetimepicker({
            format: 'Y-MM-DD'
        });
        $('#datetimepicker2').datetimepicker({
            format: 'Y-MM-DD'
        });
    });
</script>
</body>
</html>


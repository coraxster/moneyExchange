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

    <h1>Hello {{ $wallet->user->name }}! (WalletId: {{ $wallet->id }})</h1>
    <div class="row">
        <div class="col-4">
            <span class="badge badge-success">+ {{ $overall[$wallet->currency]['deposit'] }} {{ $wallet->currency }}</span>
            <span class="badge badge-danger">- {{ $overall[$wallet->currency]['withdraw'] }} {{ $wallet->currency }}</span>
        </div>
        <div class="col-4">
            @if($wallet->currency !== 'USD')
                <span class="badge badge-success">+ {{ $overall['USD']['deposit'] }} USD</span>
                <span class="badge badge-danger">- {{$overall['USD']['withdraw']}} USD</span>
            @endif
        </div>
    </div>
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
                    <th scope="col">OpId</th>
                    <th scope="col">From</th>
                    <th scope="col">Amount</th>
                    <th scope="col">To</th>
                    <th scope="col">Date</th>
                </tr>
                </thead>
                <tbody>
                @forelse($ops as $op)
                    @if($wallet->id === $op->toWallet->id)
                        <tr class="table-success">
                            <td>{{ $op->id }}</td>
                            <td><a href="{{ $op->fromWallet->id ?? '#' }}">{{ $op->fromWallet->user->name ?? '' }}</a></td>
                            <td> ({{ $op->deposit }} {{ $op->deposit_money->getCurrency() }}) {{ $op->operation }} => </td>
                            <td>{{ $op->toWallet->user->name ?? '-' }}</td>
                    @else
                        <tr class="table-danger">
                            <td>{{ $op->id }}</td>
                            <td>{{ $op->fromWallet->user->name ?? '-' }}</td>
                            <td> ({{ $op->withdraw }} {{$op->withdraw_money->getCurrency()}}) {{ $op->operation }} => </td>
                            <td><a href="{{ $op->toWallet->id ?? '#' }}">{{ $op->toWallet->user->name ?? '-' }}</a></td>
                    @endif
                            <td>{{ $op->created_at }}</td>
                        </tr>
                @empty
                    <tr>
                        <td align="center" colspan="5"><p>nothing</p></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $ops->links() }}
            @if($ops->count())
                <a href="{{ url("/report/{$wallet->id}/csv") }}?{{ request()->getQueryString() }}" class="btn btn-primary search-btn">Download CSV</a>
            @endif
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


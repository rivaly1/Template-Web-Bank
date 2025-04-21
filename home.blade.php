public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        if (Auth::user()->role == 'admin') {
            $user = User::all()->count();
            $userAll = User::all();
            $mutasi = Wallet::where('status', 'done')->orderBy('created_at', 'DESC')->get();

            return view('home', compact('user', 'userAll', 'mutasi'));
        }

        if (Auth::user()->role == 'bank') {
            $wallet = Wallet::where('status', 'done')->get();
            $credit = 0;
            $debit = 0;

            foreach ($wallet as $item) {
                $credit += $item->credit;
                $debit += $item->debit;
            }

            $saldo = $credit - $debit;
            $nasabah = User::where('role', 'siswa')->count();
            $request_payment = Wallet::where('status', 'process')->orderBy('created_at', 'DESC')->get();
            $mutasi = Wallet::where('status', 'done')->orderBy('created_at', 'DESC')->get();
            $allmutasi = Wallet::where('status', 'done')->count();
            $users = User::where('role', 'siswa')->get();

            return view('home', compact('saldo', 'nasabah', 'request_payment', 'mutasi', 'allmutasi', 'users'));
        }

        if (Auth::user()->role == 'siswa') {
            $wallet = Wallet::where('user_id', Auth::id())->where('status', 'done')->get();
            $credit = 0;
            $debit = 0;

            foreach ($wallet as $item) {
                $credit += $item->credit;
                $debit += $item->debit;
            }

            $saldo = $credit - $debit;
            $mutasi = Wallet::where('user_id', Auth::id())->orderBy('created_at', 'DESC')->get();
            $users = User::where('role', 'siswa')->where('id', '!=', Auth::id())->get();

            return view('home', compact('saldo', 'mutasi', 'users'));
        }
    }

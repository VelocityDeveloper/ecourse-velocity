export type OrderStatus =
    | 'pending'
    | 'awaiting_confirmation'
    | 'paid'
    | 'expired'
    | 'cancelled';

export type PaymentMethod = 'bank_transfer' | 'qris';

export type BankAccount = {
    bank: string;
    account_number: string;
    account_name: string;
};

export type OrderSummary = {
    number: string;
    course_id: number | null;
    course_title: string;
    total: number;
    status: OrderStatus;
    payment_method: PaymentMethod | null;
    created_at: string | null;
    expires_at: string | null;
    paid_at: string | null;
};

export type PaymentDetails = Partial<BankAccount> & { qris_name?: string };

export type CheckoutPayment = {
    bank_accounts: BankAccount[];
    qris_url: string | null;
    qris_name: string | null;
    instructions: string | null;
    methods: PaymentMethod[];
};

export type OrderPerson = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
};

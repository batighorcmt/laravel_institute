import 'package:flutter/material.dart';
import '../../../../widgets/animated_tile.dart';
import 'fee_collection_page.dart';
import 'my_collections_page.dart';
import 'cash_deposit_page.dart';
import 'deposit_history_page.dart';
import 'detailed_due_report_page.dart';

class TeacherBillingMenuPage extends StatelessWidget {
  const TeacherBillingMenuPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Billing'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              children: [
                AnimatedTile(
                  title: 'Fee Collect',
                  icon: Icons.point_of_sale_outlined,
                  background: const Color(0xFFE0F7FA),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const FeeCollectionPage()),
                    );
                  },
                ),
                AnimatedTile(
                  title: 'My Collections',
                  icon: Icons.receipt_long_outlined,
                  background: const Color(0xFFE8F5E9),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const MyCollectionsPage()),
                    );
                  },
                ),
                AnimatedTile(
                  title: 'Cash Deposit',
                  icon: Icons.account_balance_outlined,
                  background: const Color(0xFFFFF3E0),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const CashDepositPage()),
                    );
                  },
                ),
                AnimatedTile(
                  title: 'Deposit History',
                  icon: Icons.history_outlined,
                  background: const Color(0xFFF3E5F5),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const DepositHistoryPage()),
                    );
                  },
                ),
                AnimatedTile(
                  title: 'Due Report',
                  icon: Icons.assignment_late_outlined,
                  background: const Color(0xFFFBE9E7),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const DetailedDueReportPage()),
                    );
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

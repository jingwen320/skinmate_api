# import os
# import pymysql
# import pandas as pd
# import xgboost as xgb
# from datetime import datetime

# # 1. Connect directly to your MySQL Database
# connection = pymysql.connect(
#     host='localhost',
#     user='root',
#     password='',
#     database='skinmate_db',
#     cursorclass=pymysql.cursors.DictCursor
# )

# try:
#     with connection.cursor() as cursor:
#         # Fetch orders
#         cursor.execute("SELECT order_date, final_total, status FROM orders")
#         orders_data = cursor.fetchall()
        
#         # Fetch refunds
#         cursor.execute("SELECT created_at, refund_amount, status FROM refund_requests")
#         refunds_data = cursor.fetchall()

#     if not orders_data:
#         print("No orders found in the database.")
#         exit()

#     # Convert to DataFrames
#     df_orders = pd.DataFrame(orders_data)
#     df_refunds = pd.DataFrame(refunds_data) if refunds_data else pd.DataFrame(columns=['created_at', 'refund_amount', 'status'])

#     # Filter completed or refund-rejected orders
#     df_orders['status'] = df_orders['status'].str.strip()
#     df_orders = df_orders[df_orders['status'].isin(['Completed', 'Refund Rejected'])].copy()

#     # Convert order_date to monthly period (YYYY-MM)
#     df_orders['order_date'] = pd.to_datetime(df_orders['order_date'])
#     df_orders['month'] = df_orders['order_date'].dt.to_period('M').astype(str)
    
#     # Aggregate gross sales per month
#     monthly_gross = df_orders.groupby('month')['final_total'].sum().reset_index()
#     monthly_gross.rename(columns={'final_total': 'gross_total'}, inplace=True)

#     # Process refunds if any exist
#     if not df_refunds.empty and 'status' in df_refunds.columns:
#         df_refunds['status'] = df_refunds['status'].str.strip()
#         df_refunds = df_refunds[df_refunds['status'] == 'Approved'].copy()
        
#         if not df_refunds.empty:
#             df_refunds['created_at'] = pd.to_datetime(df_refunds['created_at'])
#             df_refunds['month'] = df_refunds['created_at'].dt.to_period('M').astype(str)
#             monthly_refunds = df_refunds.groupby('month')['refund_amount'].sum().reset_index()
            
#             df = pd.merge(monthly_gross, monthly_refunds, on='month', how='left').fillna(0)
#             df['net_total'] = df['gross_total'] - df['refund_amount']
#         else:
#             df = monthly_gross.copy()
#             df['net_total'] = df['gross_total']
#     else:
#         df = monthly_gross.copy()
#         df['net_total'] = df['gross_total']

#     df = df[['month', 'net_total']].sort_values('month').reset_index(drop=True)
    
#     print("Cleaned Monthly Data Loaded:")
#     print(df)

#     if len(df) < 3:
#         print("Not enough historical data to train XGBoost yet (Need at least 3 months).")
#         exit()

#     # 3. Feature Engineering for XGBoost
#     df['month_dt'] = pd.to_datetime(df['month'] + '-01')
#     df['net_total'] = df['net_total'].astype(float)
    
#     df['year'] = df['month_dt'].dt.year
#     df['month_number'] = df['month_dt'].dt.month
#     df['lag_1'] = df['net_total'].shift(1)  # Previous month revenue
#     df['lag_2'] = df['net_total'].shift(2)  # 2 months ago revenue

#     df_model = df.dropna().reset_index(drop=True)

#     X = df_model[['year', 'month_number', 'lag_1', 'lag_2']]
#     y = df_model['net_total']

#     # 4. Train XGBoost Regressor
#     model = xgb.XGBRegressor(n_estimators=30, learning_rate=0.1, max_depth=1, random_state=42)
#     model.fit(X, y)

#     # 5. Predict Next Month
#     last_row = df.iloc[-1]
#     next_month_dt = last_row['month_dt'] + pd.DateOffset(months=1)
#     target_month_str = next_month_dt.strftime('%Y-%m')

#     next_features = pd.DataFrame([{
#         'year': next_month_dt.year,
#         'month_number': next_month_dt.month,
#         'lag_1': last_row['net_total'],
#         'lag_2': df.iloc[-2]['net_total']
#     }])

#     predicted_revenue = float(model.predict(next_features)[0])
#     predicted_revenue = max(0.0, predicted_revenue)

#     # 6. Save prediction into MySQL database
#     with connection.cursor() as cursor:
#         sql = """
#             INSERT INTO revenue_forecasts (forecast_month, predicted_amount) 
#             VALUES (%s, %s)
#             ON DUPLICATE KEY UPDATE predicted_amount = VALUES(predicted_amount), created_at = CURRENT_TIMESTAMP
#         """
#         cursor.execute(sql, (target_month_str, predicted_revenue))
#         connection.commit()
#         print(f"\nSuccessfully forecasted {target_month_str}: RM {predicted_revenue:.2f}")

# finally:
#     connection.close()


import os
import pymysql
import pandas as pd
import xgboost as xgb
from datetime import datetime

# 1. Connect directly to MySQL Database
connection = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='skinmate_db',
    cursorclass=pymysql.cursors.DictCursor
)

try:
    with connection.cursor() as cursor:
        # Fetch orders
        cursor.execute("SELECT order_date, final_total, status FROM orders")
        orders_data = cursor.fetchall()
        
        # Fetch refunds
        cursor.execute("SELECT created_at, refund_amount, status FROM refund_requests")
        refunds_data = cursor.fetchall()

    if not orders_data:
        print("No orders found in the database.")
        exit()

    # Convert to DataFrames
    df_orders = pd.DataFrame(orders_data)
    df_refunds = pd.DataFrame(refunds_data) if refunds_data else pd.DataFrame(columns=['created_at', 'refund_amount', 'status'])

    # Filter completed or refund-rejected orders
    df_orders['status'] = df_orders['status'].str.strip()
    df_orders = df_orders[df_orders['status'].isin(['Completed', 'Refund Rejected'])].copy()

    # Convert order_date to monthly period (YYYY-MM)
    df_orders['order_date'] = pd.to_datetime(df_orders['order_date'])
    df_orders['month'] = df_orders['order_date'].dt.to_period('M').astype(str)
    
    # Aggregate gross sales per month
    monthly_gross = df_orders.groupby('month')['final_total'].sum().reset_index()
    monthly_gross.rename(columns={'final_total': 'gross_total'}, inplace=True)

    # Process refunds if any exist
    if not df_refunds.empty and 'status' in df_refunds.columns:
        df_refunds['status'] = df_refunds['status'].str.strip()
        df_refunds = df_refunds[df_refunds['status'] == 'Approved'].copy()
        
        if not df_refunds.empty:
            df_refunds['created_at'] = pd.to_datetime(df_refunds['created_at'])
            df_refunds['month'] = df_refunds['created_at'].dt.to_period('M').astype(str)
            monthly_refunds = df_refunds.groupby('month')['refund_amount'].sum().reset_index()
            
            df = pd.merge(monthly_gross, monthly_refunds, on='month', how='left').fillna(0)
            df['net_total'] = df['gross_total'] - df['refund_amount']
        else:
            df = monthly_gross.copy()
            df['net_total'] = df['gross_total']
    else:
        df = monthly_gross.copy()
        df['net_total'] = df['gross_total']

    df = df[['month', 'net_total']].sort_values('month').reset_index(drop=True)

    # 2. HANDLE ZERO-ORDER MONTHS: Ensure continuous monthly sequence up to current month
    df['month_dt'] = pd.to_datetime(df['month'] + '-01')
    df = df.set_index('month_dt')
    
    # Generate a full monthly date range from your first recorded month to the current month
    full_range = pd.date_range(start=df.index.min(), end=pd.Timestamp.today().normalize(), freq='MS')
    
    # Reindex to fill any gaps with 0.00
    df = df.reindex(full_range, fill_value=0.0).reset_index()
    df.rename(columns={'index': 'month_dt'}, inplace=True)
    df['month'] = df['month_dt'].dt.strftime('%Y-%m')
    df['net_total'] = df['net_total'].astype(float)

    print("Cleaned Monthly Data Loaded (with zero-gaps handled):")
    print(df[['month', 'net_total']])

    if len(df) < 3:
        print("Not enough historical data to train XGBoost yet (Need at least 3 months).")
        exit()

    # 3. Feature Engineering for XGBoost
    df['year'] = df['month_dt'].dt.year
    df['month_number'] = df['month_dt'].dt.month
    df['lag_1'] = df['net_total'].shift(1)  # Previous month revenue
    df['lag_2'] = df['net_total'].shift(2)  # 2 months ago revenue

    df_model = df.dropna().reset_index(drop=True)

    X = df_model[['year', 'month_number', 'lag_1', 'lag_2']]
    y = df_model['net_total']

    # 4. Train XGBoost Regressor
    model = xgb.XGBRegressor(n_estimators=30, learning_rate=0.1, max_depth=1, random_state=42)
    model.fit(X, y)

    # 5. Predict Next Month
    last_row = df.iloc[-1]
    next_month_dt = last_row['month_dt'] + pd.DateOffset(months=1)
    target_month_str = next_month_dt.strftime('%Y-%m')

    next_features = pd.DataFrame([{
        'year': next_month_dt.year,
        'month_number': next_month_dt.month,
        'lag_1': last_row['net_total'],
        'lag_2': df.iloc[-2]['net_total']
    }])

    predicted_revenue = float(model.predict(next_features)[0])
    predicted_revenue = max(0.0, predicted_revenue)

    # 6. Save prediction into MySQL database
    with connection.cursor() as cursor:
        sql = """
            INSERT INTO revenue_forecasts (forecast_month, predicted_amount) 
            VALUES (%s, %s)
            ON DUPLICATE KEY UPDATE predicted_amount = VALUES(predicted_amount), created_at = CURRENT_TIMESTAMP
        """
        cursor.execute(sql, (target_month_str, predicted_revenue))
        connection.commit()
        print(f"\nSuccessfully forecasted {target_month_str}: RM {predicted_revenue:.2f}")

finally:
    connection.close()
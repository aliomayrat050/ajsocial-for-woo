<div><strong>RABATT - Mehr kaufen, weniger zahlen</strong></div>
    
<table>
        <thead>
            <tr>
                <?php foreach ($discountData as $rule): ?>
                    <th><?= htmlspecialchars($rule['quantity']) ?>+</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($discountData as $rule): ?>
                    <td><?= htmlspecialchars($rule['discount'] * 100) ?>%</td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>


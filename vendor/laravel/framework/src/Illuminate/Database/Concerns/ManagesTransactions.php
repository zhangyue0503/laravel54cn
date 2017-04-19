<?php

namespace Illuminate\Database\Concerns;

use Closure;
use Exception;
use Throwable;
//管理事务
trait ManagesTransactions
{
    /**
     * Execute a Closure within a transaction.
     *
     * 在事务中执行闭包
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction(); //启动一个新的数据库事务

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            //
            // 我们将简单地执行给定的回调在一个try/catch块，如果我们赶上任何异常，我们可以回滚此事务，使这一切都没有得到真正坚持到一个数据库或存储在一个永久的时尚
            //
            try {
                //用给定的值调用给定的闭包，然后返回值
                return tap($callback($this), function ($result) {
                    $this->commit(); //提交活动数据库事务
                });
            }

            // If we catch an exception we'll rollback this transaction and try again if we
            // are not out of attempts. If we are out of attempts we will just throw the
            // exception back out and let the developer handle an uncaught exceptions.
            //
            // 如果我们捕获异常，我们将回滚这个事务，如果我们没有尝试，我们会再试一次
            // 如果我们不尝试，我们就会抛出异常退出，让开发者处理未捕获的异常
            //
            catch (Exception $e) {
                $this->handleTransactionException( //处理运行事务语句时遇到的异常
                    $e, $currentAttempt, $attempts
                );
            } catch (Throwable $e) {
                $this->rollBack(); //回滚活动数据库事务

                throw $e;
            }
        }
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * 处理运行事务语句时遇到的异常
     *
     * @param  \Exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     *
     * @throws \Exception
     */
    protected function handleTransactionException($e, $currentAttempt, $maxAttempts)
    {
        // On a deadlock, MySQL rolls back the entire transaction so we can't just
        // retry the query. We have to throw this exception all the way out and
        // let the developer handle it in another way. We will decrement too.
        //
        // 在死锁上，MySQL回滚整个事务，因此我们不能只重试查询
        // 我们必须抛出这个异常，让开发者用另一种方式处理它
        // 我们也会减少
        //
        if ($this->causedByDeadlock($e) &&  //确定给定的异常是否由死锁引起
            $this->transactions > 1) {
            --$this->transactions;

            throw $e;
        }

        // If there was an exception we will rollback this transaction and then we
        // can check if we have exceeded the maximum attempt count for this and
        // if we haven't we will return and try this query again in our loop.
        //
        // 如果有异常，我们将回滚这个事务，然后我们可以检查我们是否已经超出了最大尝试计数，如果没有，我们将返回并尝试这个查询再次在我们的循环
        //
        $this->rollBack(); //回滚活动数据库事务

        if ($this->causedByDeadlock($e) &&  //确定给定的异常是否由死锁引起
            $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    /**
     * Start a new database transaction.
     *
     * 启动一个新的数据库事务
     *
     * @return void
     * @throws \Exception
     */
    public function beginTransaction()
    {
        $this->createTransaction(); //在数据库中创建事务

        ++$this->transactions;

        $this->fireConnectionEvent('beganTransaction'); //为此连接触发事件
    }

    /**
     * Create a transaction within the database.
     *
     * 在数据库中创建事务
     *
     * @return void
     */
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            try {
                //获取当前的PDO连接->PDO::beginTransaction()
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                $this->handleBeginTransactionException($e); //处理从事务开始的异常
            }
            //                                 Illuminate\Database\Query\Grammars\Grammar::supportsSavepoints()
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint(); //在数据库中创建一个保存点
        }
    }

    /**
     * Create a save point within the database.
     *
     * 在数据库中创建一个保存点
     *
     * @return void
     */
    protected function createSavepoint()
    {
        //获取当前的PDO连接->exec(编译SQL语句来定义一个保存点)
        $this->getPdo()->exec(
            $this->queryGrammar->compileSavepoint('trans'.($this->transactions + 1))
        );
    }

    /**
     * Handle an exception from a transaction beginning.
     *
     * 处理从事务开始的异常
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleBeginTransactionException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reconnect(); //重新连接数据库
            //PDO::beginTransaction()
            $this->pdo->beginTransaction();
        } else {
            throw $e;
        }
    }

    /**
     * Commit the active database transaction.
     *
     * 提交活动数据库事务
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();//获取当前的PDO连接->PDO::commit()
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed'); //为此连接触发事件
    }

    /**
     * Rollback the active database transaction.
     *
     * 回滚活动数据库事务
     *
     * @param  int|null  $toLevel
     * @return void
     */
    public function rollBack($toLevel = null)
    {
        // We allow developers to rollback to a certain transaction level. We will verify
        // that this given transaction level is valid before attempting to rollback to
        // that level. If it's not we will just return out and not attempt anything.
        //
        // 我们允许开发人员回滚到某个事务级别
        // 我们将验证此给定的事务级别在回滚到该级别之前是有效的
        // 如果不是，我们将返回，而不是尝试任何东西
        //
        $toLevel = is_null($toLevel)
                    ? $this->transactions - 1
                    : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        // Next, we will actually perform this rollback within this database and fire the
        // rollback event. We will also set the current transaction level to the given
        // level that was passed into this method so it will be right from here out.
        //
        // 接下来，我们将在这个数据库中执行回滚，并触发回滚事件
        // 我们还将将当前事务级别设置为已传递到该方法中的给定级别，以便从这里输出
        //
        $this->performRollBack($toLevel); //在数据库中执行回滚

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');//为此连接触发事件
    }

    /**
     * Perform a rollback within the database.
     *
     * 在数据库中执行回滚
     *
     * @param  int  $toLevel
     * @return void
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $this->getPdo()->rollBack();//获取当前的PDO连接->PDO::rollBack()
        } elseif ($this->queryGrammar->supportsSavepoints()) { //确定语法支持保存点
            $this->getPdo()->exec(//获取当前的PDO连接->PDO::exec(编译SQL语句来定义一个保存点)
                $this->queryGrammar->compileSavepointRollBack('trans'.($toLevel + 1))
            );
        }
    }

    /**
     * Get the number of active transactions.
     *
     * 获取活动事务数
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }
}
